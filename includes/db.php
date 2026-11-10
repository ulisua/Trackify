<?php
// includes/db.php
// Capa de acceso a datos de Trackify. Funciona con MySQL/MariaDB y con PostgreSQL
// usando PDO; el motor se elige con DB_DRIVER en .env ("mysql" por defecto, o "pgsql").
//
// Uso (siempre con parámetros "?", nunca concatenando valores en el SQL):
//   $fila  = $conn->fila("SELECT nombre FROM usuarios WHERE id_usuario = ?", [$id]);   // array o null
//   $filas = $conn->filas("SELECT ... WHERE id_usuario = ?", [$id]);                   // lista de arrays
//   $total = $conn->valor("SELECT COUNT(*) FROM movimientos WHERE id_usuario = ?", [$id]);
//   $n     = $conn->ejecutar("DELETE FROM movimientos WHERE id_movimiento = ?", [$id]); // filas afectadas
//   $id    = $conn->insertar("INSERT INTO categorias (nombre, tipo) VALUES (?, ?)", [$n, $t], 'id_categoria');
//   $conn->transaccion(function () use ($conn) { ... });  // todo o nada
//
// Las claves de las filas siempre vienen en minúscula (PostgreSQL pasa los nombres a minúscula).

final class BaseDatos
{
    public readonly PDO $pdo;
    public readonly string $motor; // 'mysql' o 'pgsql'

    public function __construct(string $motor, string $host, ?string $puerto, string $base, string $usuario, string $clave)
    {
        $this->motor = $motor === 'pgsql' ? 'pgsql' : 'mysql';
        $dsn = $this->motor === 'pgsql'
            ? sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $puerto ?: '5432', $base)
            : sprintf('mysql:host=%s;%sdbname=%s;charset=utf8mb4', $host, $puerto ? "port=$puerto;" : '', $base);

        $this->pdo = new PDO($dsn, $usuario, $clave, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // enteros y decimales con su tipo real
            PDO::ATTR_CASE               => PDO::CASE_LOWER,
        ]);

        if ($this->motor === 'pgsql') {
            // Misma zona horaria que PHP, para que NOW() coincida con date()
            $this->pdo->exec("SET client_encoding TO 'UTF8'");
            $this->pdo->exec('SET TIME ZONE ' . $this->pdo->quote(date_default_timezone_get()));
        }
    }

    public function esPostgres(): bool
    {
        return $this->motor === 'pgsql';
    }

    // Prepara y ejecuta. Los booleanos se mandan como 0/1 (en las dos bases son enteros).
    public function consulta(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_map(fn($v) => is_bool($v) ? (int)$v : $v, array_values($params)));
        return $stmt;
    }

    // Primera fila como array asociativo, o null si no hay resultados
    public function fila(string $sql, array $params = []): ?array
    {
        $fila = $this->consulta($sql, $params)->fetch();
        return $fila === false ? null : $fila;
    }

    // Todas las filas
    public function filas(string $sql, array $params = []): array
    {
        return $this->consulta($sql, $params)->fetchAll();
    }

    // Primera columna de la primera fila, o null
    public function valor(string $sql, array $params = []): mixed
    {
        $valor = $this->consulta($sql, $params)->fetchColumn();
        return $valor === false ? null : $valor;
    }

    // INSERT / UPDATE / DELETE. Devuelve la cantidad de filas afectadas.
    public function ejecutar(string $sql, array $params = []): int
    {
        return $this->consulta($sql, $params)->rowCount();
    }

    // INSERT que devuelve el id generado. En PostgreSQL usa RETURNING (no hay lastInsertId sin secuencia).
    public function insertar(string $sql, array $params, string $columnaId): int
    {
        if ($this->esPostgres()) {
            return (int)$this->consulta($sql . ' RETURNING ' . $columnaId, $params)->fetchColumn();
        }
        $this->consulta($sql, $params);
        return (int)$this->pdo->lastInsertId();
    }

    // Ejecuta $fn dentro de una transacción: si lanza una excepción se deshace todo y se relanza.
    public function transaccion(callable $fn): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $resultado = $fn($this);
            $this->pdo->commit();
            return $resultado;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // ── Diferencias de SQL entre motores ────────────────────────────────────

    // Expresión "AAAA-MM" de una columna DATE (para agrupar por mes)
    public function sqlMes(string $columna): string
    {
        return $this->esPostgres() ? "TO_CHAR($columna, 'YYYY-MM')" : "DATE_FORMAT($columna, '%Y-%m')";
    }

    // ¿Existe la columna? (para las migraciones)
    public function columna(string $tabla, string $columna): ?array
    {
        $esquema = $this->esPostgres() ? 'current_schema()' : 'DATABASE()';
        return $this->fila(
            "SELECT data_type, is_nullable, numeric_scale, column_default, character_maximum_length
               FROM information_schema.columns
              WHERE table_schema = $esquema AND table_name = ? AND column_name = ?",
            [$tabla, $columna]
        );
    }
}

// Fecha "AAAA-MM-01" del mes de hace $meses meses (0 = mes actual). Se calcula en PHP para no
// depender de las funciones de fecha de cada motor.
function inicioDeMes(int $meses = 0): string
{
    return date('Y-m-01', strtotime("first day of -$meses months"));
}
