<?php
$page = 'ingresos';
require_once 'includes/header.php';
?>

            <h2>💸 Ingresos</h2>

            <!-- RESUMEN -->
            <section class="cards">
                <div class="card ingreso-card">
                    <h4>Total del mes</h4>
                    <p>$120.000</p>
                </div>
                <div class="card">
                    <h4>Cantidad</h4>
                    <p>8</p>
                </div>
                <div class="card">
                    <h4>Promedio</h4>
                    <p>$15.000</p>
                </div>
            </section>

            <!-- ACCIONES -->
            <div class="acciones">
                <button class="btn ingreso">+ Nuevo ingreso</button>
            </div>

            <!-- FILTROS -->
            <section class="filtros">
                <input type="date">
                <select>
                    <option>Todos</option>
                    <option>Sueldo</option>
                    <option>Freelance</option>
                    <option>Inversiones</option>
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
                    <tbody id="tablaIngresos">
                        <tr>
                            <td>15/03</td>
                            <td>Sueldo</td>
                            <td>Trabajo</td>
                            <td class="positivo">+$80.000</td>
                            <td>
                                <button class="edit">✏️</button>
                                <button class="delete">🗑️</button>
                            </td>
                        </tr>
                        <tr>
                            <td>10/03</td>
                            <td>Diseño web</td>
                            <td>Freelance</td>
                            <td class="positivo">+$40.000</td>
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
                            <span class="mc-desc">Sueldo</span>
                            <span class="mc-monto positivo">+$80.000</span>
                        </div>
                        <div class="mc-bottom">
                            <span class="mc-tag">Trabajo</span>
                            <span class="mc-fecha">15/03</span>
                            <div class="mc-acciones">
                                <button class="edit">✏️</button>
                                <button class="delete">🗑️</button>
                            </div>
                        </div>
                    </div>
                    <div class="movimiento-card">
                        <div class="mc-top">
                            <span class="mc-desc">Diseño web</span>
                            <span class="mc-monto positivo">+$40.000</span>
                        </div>
                        <div class="mc-bottom">
                            <span class="mc-tag">Freelance</span>
                            <span class="mc-fecha">10/03</span>
                            <div class="mc-acciones">
                                <button class="edit">✏️</button>
                                <button class="delete">🗑️</button>
                            </div>
                        </div>
                    </div>
                </div>

            </section>

<?php require_once 'includes/footer.php'; ?>