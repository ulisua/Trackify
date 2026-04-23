<?php
$page = 'ia';
require_once 'includes/header.php';
?>

            <h2>🤖 Preguntale a la IA</h2>

            <div class="chat-container">
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