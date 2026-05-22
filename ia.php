<?php
$page = 'ia';
require_once 'includes/header.php';
?>

            <h2 class="titulo-con-icono"><img src="iconos/generales/robot.png" alt="IA" class="icono-titulo"> Preguntale a la IA</h2>

            <!-- Background Orbs para Glassmorphism -->
            <div class="glow-orb orb-1" style="position: fixed; top: 15%; left: 25%; z-index: 0;"></div>
            <div class="glow-orb orb-2" style="position: fixed; bottom: 15%; right: 25%; z-index: 0;"></div>

            <div class="chat-container" style="position: relative; z-index: 1;">
                <div id="chat" class="chat"></div>
                <div class="input-container">
                    <textarea id="pregunta" class="input-chat"
                        placeholder="Escribí tu consulta financiera..."></textarea>
                    <button class="btn-enviar" onclick="preguntarIA()">Enviar</button>
                </div>
            </div>

<?php 
$extra_js = '<script src="js/ia.js?v=2"></script>';
require_once 'includes/footer.php'; 
?>