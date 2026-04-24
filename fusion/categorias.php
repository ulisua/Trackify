<?php
$page = 'categorias';
$extra_css = '<link rel="stylesheet" href="css/categorias.css">';
require_once 'includes/header.php';
?>

            <div class="page-header">
                <h2>🏷️ Categorías</h2>
                <button class="btn-nuevo" onclick="abrirModal()">+ Nueva categoría</button>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="switchTab('gastos', this)">Gastos</button>
                <button class="tab" onclick="switchTab('ingresos', this)">Ingresos</button>
            </div>

            <!-- Resumen distribución -->
            <div class="resumen-cats">
                <h3>Distribución del mes</h3>
                <div class="resumen-row">
                    <span class="resumen-cat-nombre">🍔 Comida</span>
                    <div class="resumen-barra">
                        <div class="resumen-barra-fill" style="width:38%;background:#F97316"></div>
                    </div>
                    <span class="resumen-pct">38%</span>
                </div>
                <div class="resumen-row">
                    <span class="resumen-cat-nombre">🚌 Transporte</span>
                    <div class="resumen-barra">
                        <div class="resumen-barra-fill" style="width:18%;background:#3B82F6"></div>
                    </div>
                    <span class="resumen-pct">18%</span>
                </div>
                <div class="resumen-row">
                    <span class="resumen-cat-nombre">💡 Servicios</span>
                    <div class="resumen-barra">
                        <div class="resumen-barra-fill" style="width:25%;background:#8B5CF6"></div>
                    </div>
                    <span class="resumen-pct">25%</span>
                </div>
                <div class="resumen-row">
                    <span class="resumen-cat-nombre">🎬 Entretenimiento</span>
                    <div class="resumen-barra">
                        <div class="resumen-barra-fill" style="width:10%;background:#EC4899"></div>
                    </div>
                    <span class="resumen-pct">10%</span>
                </div>
                <div class="resumen-row">
                    <span class="resumen-cat-nombre">🏥 Salud</span>
                    <div class="resumen-barra">
                        <div class="resumen-barra-fill" style="width:9%;background:#10B981"></div>
                    </div>
                    <span class="resumen-pct">9%</span>
                </div>
            </div>

            <!-- Grid de categorías — GASTOS -->
            <div class="cat-grid" id="gridGastos">
                <div class="cat-card cat-comida">
                    <div class="cat-icon">🍔</div>
                    <div class="cat-nombre">Comida</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$32.300</span>
                        <span class="cat-cant">14 movimientos</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:38%;background:#F97316"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
                <div class="cat-card cat-transporte">
                    <div class="cat-icon">🚌</div>
                    <div class="cat-nombre">Transporte</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$15.200</span>
                        <span class="cat-cant">8 movimientos</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:18%;background:#3B82F6"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
                <div class="cat-card cat-servicios">
                    <div class="cat-icon">💡</div>
                    <div class="cat-nombre">Servicios</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$21.000</span>
                        <span class="cat-cant">3 movimientos</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:25%;background:#8B5CF6"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
                <div class="cat-card cat-entrete">
                    <div class="cat-icon">🎬</div>
                    <div class="cat-nombre">Entretenimiento</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$8.500</span>
                        <span class="cat-cant">5 movimientos</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:10%;background:#EC4899"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
                <div class="cat-card cat-salud">
                    <div class="cat-icon">🏥</div>
                    <div class="cat-nombre">Salud</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$7.500</span>
                        <span class="cat-cant">2 movimientos</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:9%;background:#10B981"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
                <div class="cat-card cat-otros">
                    <div class="cat-icon">📦</div>
                    <div class="cat-nombre">Otros</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$500</span>
                        <span class="cat-cant">1 movimiento</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:1%;background:#94A3B8"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
            </div>

            <!-- Grid de categorías — INGRESOS (oculto por defecto) -->
            <div class="cat-grid" id="gridIngresos" style="display:none">
                <div class="cat-card cat-trabajo">
                    <div class="cat-icon">💼</div>
                    <div class="cat-nombre">Trabajo</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$80.000</span>
                        <span class="cat-cant">1 movimiento</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:67%;background:#CFF27C"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
                <div class="cat-card cat-freelance">
                    <div class="cat-icon">🖥️</div>
                    <div class="cat-nombre">Freelance</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$40.000</span>
                        <span class="cat-cant">1 movimiento</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:33%;background:#EA73F5"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
            </div>

        </main>
<?php 
$extra_js = '<script src="js/categorias.js"></script>';
require_once 'includes/footer.php'; 
?>