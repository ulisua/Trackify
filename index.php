<?php
$page = 'dashboard';
require_once 'includes/header.php';
?>

<!-- BOTON FLOTANTE IA -->
<div id="botonIA" class="boton-ia" onclick="toggleChat()">💬</div>

<!-- CHAT FLOTANTE -->
<div id="chatFlotante" class="chat-flotante oculto">
    <div class="chat-header">
        <span>Asistente IA</span>
        <span onclick="toggleChat()" style="cursor:pointer;">✖</span>
    </div>
    <div id="chat" class="chat"></div>
    <div class="input-container">
        <textarea id="pregunta" class="input-chat" placeholder="Escribí tu consulta..."></textarea>
        <button class="btn-enviar" onclick="preguntarIA()">Enviar</button>
    </div>
</div>

        <!-- CARDS PRINCIPALES -->
        <section class="cards">
            <div class="card">
                <h4>Ingresos</h4>
                <p id="ingresos">$0</p>
            </div>
            <div class="card highlight">
                <h4>Balance</h4>
                <p id="balance">$0</p>
            </div>
            <div class="card">
                <h4>Gastos</h4>
                <p id="gastos">$0</p>
            </div>
        </section>

        <!-- CARDS SECUNDARIAS -->
        <section class="cards small">
            <div class="card">% gasto <p id="porcentaje">0%</p></div>
            <div class="card">Mayor categoría <p id="categoriaTop">-</p></div>
            <div class="card">Gasto mensual <p id="gastoMensual">$0</p></div>
        </section>

        <!-- GRAFICOS -->
        <section class="graficos">
            <div class="grafico-box">
                <canvas id="graficoTorta"></canvas>
            </div>
            <div class="grafico-box">
                <canvas id="graficoBarras"></canvas>
            </div>
        </section>

        <!-- BOTONES -->
        <div class="acciones">
            <button class="btn ingreso" onclick="abrirModal('ingreso')">+ Ingreso</button>
            <button class="btn gasto" onclick="abrirModal('gasto')">+ Gasto</button>
            <button class="btn objetivo" onclick="abrirModal('objetivo')">+ Objetivo</button>
        </div>

        <!-- GRID -->
        <section class="grid">
            <div class="box">
                <h3>Movimientos</h3>
                <ul id="movimientos"></ul>
            </div>
            <div class="box">
                <h3>Recomendaciones IA</h3>
                <p id="recomendacion"></p>
            </div>
        </section>

    </main>
</div>

<!-- MODAL -->
<div id="modal" class="modal hidden">
    <div class="modal-content">
        <h3 id="modalTitulo"></h3>
        <input type="number" id="monto" placeholder="Monto">
        <input type="date" id="fecha">
        <input type="text" id="extra" placeholder="Descripción / Categoría">
        <div class="modal-actions">
            <button class="btn" onclick="guardar()">Guardar</button>
            <button class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </div>
</div>

<?php 
$extra_js = '<script src="js/ia.js?v=2"></script>';
require_once 'includes/footer.php'; 
?>