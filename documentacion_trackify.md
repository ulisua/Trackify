# Documentación Completa del Proyecto: Trackify 🚀

¡Bienvenido a la documentación oficial de **Trackify**! 

Este documento fue creado especialmente para que **cualquier persona**, incluso sin tener conocimientos previos de programación, bases de datos o tecnología, pueda entender exactamente qué es este proyecto, cómo funciona, y qué hace cada una de sus partes.

Siéntate cómodo, vamos a ir paso a paso.

---

## 1. ¿Qué es Trackify? 🤔

**Trackify** es una aplicación web de gestión financiera personal. Su objetivo principal es ayudar a las personas a llevar un control claro y sencillo de su dinero: cuánto ganan (ingresos), cuánto gastan (egresos) y cuánto logran ahorrar para cumplir sus sueños (objetivos o metas de ahorro).

Imagina que es una libreta de anotaciones financiera, pero inteligente, automática y visual. Te muestra gráficos, te avisa si estás gastando demasiado y simula darte consejos mediante un "Asistente de Inteligencia Artificial (IA)".

---

## 2. Tecnologías Utilizadas 💻

Para construir esta aplicación, se utilizaron diferentes "idiomas" o lenguajes que las computadoras entienden:

1. **HTML & CSS**: Son los lenguajes de diseño. HTML es como los ladrillos de una casa (pone los textos, imágenes, botones), y CSS es la pintura y decoración (colores, tamaños, animaciones).
2. **JavaScript (JS)**: Es el lenguaje que le da "vida" o interactividad a la página sin necesidad de recargarla. Por ejemplo, cuando se abren ventanas emergentes o los gráficos se dibujan en pantalla de forma bonita. También se usa una herramienta externa llamada **Chart.js** que dibuja los gráficos de barras y tortas automáticamente.
3. **PHP**: Es el "cerebro" detrás de escena. Mientras tú ves la pantalla bonita en tu navegador, PHP está en el servidor procesando los cálculos matemáticos (como restar gastos de ingresos para darte el balance), procesando inicios de sesión seguros y conectándose a la base de datos.
4. **MySQL (Base de datos)**: Es el "archivo o cajón" donde se guarda la información de forma permanente. Si no existiera, cada vez que cerraras la página perderías todos tus datos.

---

## 3. La Base de Datos: El Corazón del Sistema 🗄️

Como mencionamos, MySQL guarda toda la información en "Tablas" (imagínalo como hojas de cálculo de Excel interconectadas). Vamos a explicar cada tabla que existe en el proyecto:

### A. Tabla `usuarios`
Guarda la información de las personas que se registran en la plataforma.
*   **Concepto:** Cada usuario tiene su propia cuenta privada.
*   **Datos que guarda:** ID (número de identificación único), Nombre, Email, Clave (guardada de forma encriptada, como un código secreto para que nadie la lea), y la fecha en que se registró.

### B. Tabla `categorias`
Clasifica de dónde viene el dinero o en qué se va.
*   **Concepto:** No es lo mismo gastar en "Comida" que en "Transporte". Esta tabla guarda las etiquetas.
*   **Datos que guarda:** ID, Nombre de la categoría ("Servicios", "Sueldo"), y el Tipo (si es para un "ingreso" o para un "gasto").

### C. Tabla `movimientos`
Es el registro principal de la aplicación. Aquí se anota cada billete que entra o sale.
*   **Concepto:** El historial financiero.
*   **Datos que guarda:** 
    *   Quién hizo el movimiento (relacionado al usuario).
    *   A qué categoría pertenece.
    *   Monto de dinero.
    *   Tipo (Ingreso o Gasto).
    *   Descripción (Ej: "Compra en el supermercado").
    *   Fecha.
    *   Si es un gasto "hormiga" (esos pequeños gastos diarios que no notamos pero suman, como un café).

### D. Tabla `metas_ahorro`
Guarda los sueños financieros del usuario.
*   **Concepto:** "Quiero juntar $50.000 para un viaje".
*   **Datos que guarda:** Nombre de la meta, Descripción, Monto que se quiere alcanzar, Monto que ya se ha juntado, Fecha límite, y el estado (si está "activa", "inactiva" o "lograda").

### E. Tabla `consejos_ia`
Guarda el historial de las interacciones con el Asistente Inteligente.
*   **Concepto:** Un registro de los consejos que el sistema te ha dado.
*   **Datos que guarda:** El mensaje del consejo y a qué usuario se le dio.

> **Conexión entre tablas:** Las tablas no están sueltas. El sistema sabe perfectamente que un "movimiento" le pertenece a un "usuario" específico porque sus "ID" están vinculados. Esto garantiza que un usuario nunca vea los gastos de otro.

---

## 4. Archivos del Sistema y sus Funciones 📁

El proyecto está dividido en varios archivos. Cada archivo cumple un rol específico, como si fueran los empleados de una empresa.

### Archivos de Configuración Base
*   **`conexion.php`**: Es el "telefonista". Su único trabajo es llamar a la base de datos MySQL y decirle: *"Hola, la página web necesita hablar contigo"*. Además, cuando se instala el sistema por primera vez, crea automáticamente las categorías por defecto (Ej: Sueldo, Alimentos, Transporte).
*   **`trackify.sql`**: No es un archivo de la página web, sino las "instrucciones de montaje" de la base de datos. Se usa para crear las tablas vacías la primera vez.

### Archivos de Acceso (Seguridad)
*   **`registro.php`**: Es el formulario donde un usuario nuevo crea su cuenta. Toma la contraseña, la convierte en un código indescifrable (encriptación) y la guarda.
*   **`login.php`**: Es el "guardia de seguridad". Te pide email y contraseña. Si coinciden con lo guardado, te da una "llave" temporal (llamada Sesión) para que puedas entrar.
*   **`logout.php`**: Destruye la llave temporal, cerrando tu sesión por seguridad.

### Archivos de la Aplicación Principal
*   **`index.php` (Dashboard / Panel de Control)**: Es la pantalla principal, la sala de estar de la aplicación. 
    *   **¿Qué hace?** Calcula matemáticamente tu "Balance" (Ingresos totales - Gastos totales).
    *   Muestra los últimos 8 movimientos.
    *   Dibuja un gráfico de torta mostrando en qué gastas más.
    *   Dibuja un gráfico de barras comparando tus ingresos y gastos de los últimos 6 meses.
    *   Evalúa tu salud financiera ("Estás gastando el 80% de tu sueldo, ¡cuidado!") y te da recomendaciones simuladas.
*   **`ingresos.php` / `gastos.php`**: Pantallas específicas donde puedes ver la lista completa (el historial) solo de tus ingresos o solo de tus gastos.
*   **`objetivos.php`**: La pantalla de los sueños. Te permite crear una meta (ej: "Comprar bicicleta"), definir cuánta plata necesitas, e ir sumando dinero poco a poco. Te muestra una barra de progreso que se va llenando a medida que te acercas al 100%.
*   **`categorias.php`**: Permite al usuario crear nuevas "etiquetas" personalizadas si las que vienen por defecto no le alcanzan. (Ej: Crear una categoría de gasto llamada "Comida de mi perro").
*   **`perfil.php`**: Donde el usuario puede ver su información personal.
*   **`ia.php` y Chat Flotante**: Un sistema que interactúa con el usuario simulando una Inteligencia Artificial para dar consejos financieros basados en los datos del usuario.

### Carpetas de Diseño y Dinamismo
*   **`css/styles.css`**: Aquí están todas las reglas visuales. "Que los botones sean verdes, que tengan bordes redondeados, que cuando pases el mouse cambien de color".
*   **`js/` (JavaScript)**: Aquí están los scripts que hacen que los menús se abran, que el chat envíe mensajes, y que los gráficos se configuren.

---

## 5. Conceptos Clave del Negocio 💡

Para entender perfectamente qué hace la aplicación, repasemos sus reglas lógicas de finanzas:

1.  **El Balance**: El sistema siempre lo calcula restando. Si ganas $100 y gastas $80, tu balance es $20 (Positivo). Si gastas $120, tu balance es -$20 (Negativo o en rojo). Trackify detecta esto y te lo advierte.
2.  **Porcentaje de Gasto**: El sistema vigila qué porción de tu sueldo estás gastando. Los expertos financieros recomiendan no gastar más del 50-70% para poder ahorrar. Si en Trackify superas el 80%, el sistema enciende alertas rojas.
3.  **Progreso de Metas**: Las metas no se vinculan directamente al gasto general, funcionan como "alcancías separadas". El usuario ingresa a la meta y le inyecta dinero directamente. El sistema calcula qué porcentaje llevas: `(Dinero depositado / Dinero que necesitas) * 100`.
4.  **Aislamiento de Usuarios**: El sistema funciona mediante "Sesiones". Cuando entras, PHP memoriza tu "ID_Usuario". A partir de ese momento, **todas** las consultas a la base de datos dicen *"Tráeme los gastos, pero SOLO si el ID_Usuario es el mío"*. Es imposible que veas datos de otra persona.

## Resumen

Trackify es una aplicación moderna que une un **Frontend** (lo bonito y visual que ves gracias a HTML, CSS y JS) con un **Backend** (el cerebro lógico que procesa números y conecta con la base de datos gracias a PHP y MySQL) para brindarle a las personas una herramienta de control total sobre su vida financiera.
