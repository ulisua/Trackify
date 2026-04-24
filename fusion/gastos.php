<?php
$page = 'gastos';
require_once 'includes/header.php';
?>

        <h2>💸 Gastos</h2>

        <!-- RESUMEN -->
        <section class="cards">
            <div class="card gasto-card">
                <h4>Total del mes</h4>
                <p>$85.000</p>
            </div>
            <div class="card">
                <h4>Cantidad</h4>
                <p>12</p>
            </div>
            <div class="card">
                <h4>Promedio</h4>
                <p>$7.083</p>
            </div>
        </section>

        <!-- ACCIONES -->
        <div class="acciones">
            <button class="btn gasto">+ Nuevo gasto</button>
        </div>

        <!-- FILTROS -->
        <section class="filtros">
            <input type="date">
            <select>
                <option>Todos</option>
                <option>Comida</option>
                <option>Transporte</option>
                <option>Servicios</option>
                <option>Entretenimiento</option>
                <option>Salud</option>
            </select>
        </section>

        <!-- TABLA (desktop) / CARDS (mobile) -->
        <section class="tabla-box">

            <!-- Tabla para desktop -->
            <table class="tabla tabla-desktop">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Monto</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tablaGastos">
                    <tr>
                        <td>20/03</td>
                        <td>Supermercado</td>
                        <td>Comida</td>
                        <td class="negativo">-$25.000</td>
                        <td>
                            <button class="edit">✏️</button>
                            <button class="delete">🗑️</button>
                        </td>
                    </tr>
                    <tr>
                        <td>18/03</td>
                        <td>SUBE / Colectivo</td>
                        <td>Transporte</td>
                        <td class="negativo">-$4.500</td>
                        <td>
                            <button class="edit">✏️</button>
                            <button class="delete">🗑️</button>
                        </td>
                    </tr>
                    <tr>
                        <td>15/03</td>
                        <td>Netflix</td>
                        <td>Entretenimiento</td>
                        <td class="negativo">-$7.000</td>
                        <td>
                            <button class="edit">✏️</button>
                            <button class="delete">🗑️</button>
                        </td>
                    </tr>
                    <tr>
                        <td>12/03</td>
                        <td>Luz / Gas</td>
                        <td>Servicios</td>
                        <td class="negativo">-$18.000</td>
                        <td>
                            <button class="edit">✏️</button>
                            <button class="delete">🗑️</button>
                        </td>
                    </tr>
                    <tr>
                        <td>08/03</td>
                        <td>Farmacia</td>
                        <td>Salud</td>
                        <td class="negativo">-$9.500</td>
                        <td>
                            <button class="edit">✏️</button>
                            <button class="delete">🗑️</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Cards para mobile -->
            <div class="movimiento-cards" id="movimientoCards">
                <div class="movimiento-card">
                    <div class="mc-top">
                        <span class="mc-desc">Supermercado</span>
                        <span class="mc-monto negativo">-$25.000</span>
                    </div>
                    <div class="mc-bottom">
                        <span class="mc-tag">Comida</span>
                        <span class="mc-fecha">20/03</span>
                        <div class="mc-acciones">
                            <button class="edit">✏️</button>
                            <button class="delete">🗑️</button>
                        </div>
                    </div>
                </div>
                <div class="movimiento-card">
                    <div class="mc-top">
                        <span class="mc-desc">SUBE / Colectivo</span>
                        <span class="mc-monto negativo">-$4.500</span>
                    </div>
                    <div class="mc-bottom">
                        <span class="mc-tag">Transporte</span>
                        <span class="mc-fecha">18/03</span>
                        <div class="mc-acciones">
                            <button class="edit">✏️</button>
                            <button class="delete">🗑️</button>
                        </div>
                    </div>
                </div>
                <div class="movimiento-card">
                    <div class="mc-top">
                        <span class="mc-desc">Netflix</span>
                        <span class="mc-monto negativo">-$7.000</span>
                    </div>
                    <div class="mc-bottom">
                        <span class="mc-tag">Entretenimiento</span>
                        <span class="mc-fecha">15/03</span>
                        <div class="mc-acciones">
                            <button class="edit">✏️</button>
                            <button class="delete">🗑️</button>
                        </div>
                    </div>
                </div>
                <div class="movimiento-card">
                    <div class="mc-top">
                        <span class="mc-desc">Luz / Gas</span>
                        <span class="mc-monto negativo">-$18.000</span>
                    </div>
                    <div class="mc-bottom">
                        <span class="mc-tag">Servicios</span>
                        <span class="mc-fecha">12/03</span>
                        <div class="mc-acciones">
                            <button class="edit">✏️</button>
                            <button class="delete">🗑️</button>
                        </div>
                    </div>
                </div>
                <div class="movimiento-card">
                    <div class="mc-top">
                        <span class="mc-desc">Farmacia</span>
                        <span class="mc-monto negativo">-$9.500</span>
                    </div>
                    <div class="mc-bottom">
                        <span class="mc-tag">Salud</span>
                        <span class="mc-fecha">08/03</span>
                        <div class="mc-acciones">
                            <button class="edit">✏️</button>
                            <button class="delete">🗑️</button>
                        </div>
                    </div>
                </div>
            </div>

        </section>

<?php require_once 'includes/footer.php'; ?>
