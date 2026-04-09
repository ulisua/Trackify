<?php
$page = 'objetivos';
$extra_css = '<link rel="stylesheet" href="css/objetivos.css">';
require_once 'includes/header.php';
?>

            <div class="page-header">
                <h2>🎯 Objetivos de ahorro</h2>
                <button class="btn-nuevo" onclick="abrirModal()">+ Nuevo objetivo</button>
            </div>

            <!-- Stats -->
            <div class="obj-stats">
                <div class="obj-stat">
                    <span class="obj-stat-num">4</span>
                    <span class="obj-stat-label">Activos</span>
                </div>
                <div class="obj-stat">
                    <span class="obj-stat-num">1</span>
                    <span class="obj-stat-label">Logrados</span>
                </div>
                <div class="obj-stat">
                    <span class="obj-stat-num">$68%</span>
                    <span class="obj-stat-label">Promedio</span>
                </div>
            </div>

            <!-- Objetivos -->
            <div class="obj-lista">

                <!-- Completado -->
                <div class="obj-card completado">
                    <div class="obj-card-top">
                        <div class="obj-info">
                            <div class="obj-nombre">
                                🏖️ Vacaciones en Brasil
                                <span class="obj-badge badge-logrado">Logrado</span>
                            </div>
                            <div class="obj-desc">Viaje de verano con ahorros</div>
                        </div>
                        <div class="obj-montos">
                            <div class="obj-actual">$150.000</div>
                            <div class="obj-meta">de $150.000</div>
                        </div>
                    </div>
                    <div class="obj-progress-wrap">
                        <div class="obj-progress-info">
                            <span class="obj-pct pct-verde">100%</span>
                            <span class="obj-fecha">Completado el 10/03</span>
                        </div>
                        <div class="obj-barra">
                            <div class="obj-barra-fill fill-verde" style="width:100%"></div>
                        </div>
                    </div>
                    <div class="obj-acciones">
                        <button class="btn-editar-obj">✏️ Editar</button>
                        <button class="btn-eliminar-obj">🗑️ Eliminar</button>
                    </div>
                </div>

                <!-- Activo 1 -->
                <div class="obj-card">
                    <div class="obj-card-top">
                        <div class="obj-info">
                            <div class="obj-nombre">
                                💻 Notebook nueva
                                <span class="obj-badge badge-activo">Activo</span>
                            </div>
                            <div class="obj-desc">Reemplazar la que tengo por una más potente</div>
                        </div>
                        <div class="obj-montos">
                            <div class="obj-actual">$280.000</div>
                            <div class="obj-meta">de $500.000</div>
                        </div>
                    </div>
                    <div class="obj-progress-wrap">
                        <div class="obj-progress-info">
                            <span class="obj-pct pct-lila">56%</span>
                            <span class="obj-fecha">Vence: 01/08/2026</span>
                        </div>
                        <div class="obj-barra">
                            <div class="obj-barra-fill fill-lila" style="width:56%"></div>
                        </div>
                    </div>
                    <div class="obj-acciones">
                        <button class="btn-agregar">+ Agregar ahorro</button>
                        <button class="btn-editar-obj">✏️ Editar</button>
                        <button class="btn-eliminar-obj">🗑️</button>
                    </div>
                </div>

                <!-- Activo 2 -->
                <div class="obj-card">
                    <div class="obj-card-top">
                        <div class="obj-info">
                            <div class="obj-nombre">
                                🚗 Auto
                                <span class="obj-badge badge-activo">Activo</span>
                            </div>
                            <div class="obj-desc">Fondo inicial para el primer auto</div>
                        </div>
                        <div class="obj-montos">
                            <div class="obj-actual">$1.200.000</div>
                            <div class="obj-meta">de $3.000.000</div>
                        </div>
                    </div>
                    <div class="obj-progress-wrap">
                        <div class="obj-progress-info">
                            <span class="obj-pct pct-naranja">40%</span>
                            <span class="obj-fecha">Vence: 01/12/2027</span>
                        </div>
                        <div class="obj-barra">
                            <div class="obj-barra-fill fill-naranja" style="width:40%"></div>
                        </div>
                    </div>
                    <div class="obj-acciones">
                        <button class="btn-agregar">+ Agregar ahorro</button>
                        <button class="btn-editar-obj">✏️ Editar</button>
                        <button class="btn-eliminar-obj">🗑️</button>
                    </div>
                </div>

                <!-- Activo 3 -->
                <div class="obj-card">
                    <div class="obj-card-top">
                        <div class="obj-info">
                            <div class="obj-nombre">
                                📚 Curso de programación
                                <span class="obj-badge badge-activo">Activo</span>
                            </div>
                            <div class="obj-desc">Bootcamp full-stack online</div>
                        </div>
                        <div class="obj-montos">
                            <div class="obj-actual">$60.000</div>
                            <div class="obj-meta">de $80.000</div>
                        </div>
                    </div>
                    <div class="obj-progress-wrap">
                        <div class="obj-progress-info">
                            <span class="obj-pct pct-azul">75%</span>
                            <span class="obj-fecha">Vence: 15/04/2026</span>
                        </div>
                        <div class="obj-barra">
                            <div class="obj-barra-fill fill-azul" style="width:75%"></div>
                        </div>
                    </div>
                    <div class="obj-acciones">
                        <button class="btn-agregar">+ Agregar ahorro</button>
                        <button class="btn-editar-obj">✏️ Editar</button>
                        <button class="btn-eliminar-obj">🗑️</button>
                    </div>
                </div>

                <!-- Pausado -->
                <div class="obj-card">
                    <div class="obj-card-top">
                        <div class="obj-info">
                            <div class="obj-nombre">
                                🏠 Departamento
                                <span class="obj-badge badge-pausado">Pausado</span>
                            </div>
                            <div class="obj-desc">Fondo para entrada de un depto propio</div>
                        </div>
                        <div class="obj-montos">
                            <div class="obj-actual">$500.000</div>
                            <div class="obj-meta">de $5.000.000</div>
                        </div>
                    </div>
                    <div class="obj-progress-wrap">
                        <div class="obj-progress-info">
                            <span class="obj-pct pct-rosa">10%</span>
                            <span class="obj-fecha">Sin vencimiento</span>
                        </div>
                        <div class="obj-barra">
                            <div class="obj-barra-fill fill-rosa" style="width:10%"></div>
                        </div>
                    </div>
                    <div class="obj-acciones">
                        <button class="btn-agregar">▶ Reanudar</button>
                        <button class="btn-editar-obj">✏️ Editar</button>
                        <button class="btn-eliminar-obj">🗑️</button>
                    </div>
                </div>

            </div>

        </main>
<?php 
$extra_js = '<script src="js/objetivos.js"></script>';
require_once 'includes/footer.php'; 
?>