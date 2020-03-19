					</section>
				</main>
			</div>
		</div>
	</body>
	<script src="../build/jquery-3.4.1.slim.min.js" type="text/javascript"></script>
	<script src="../popper.min.js" type="text/javascript"></script>
	<script src="../bootstrap.min.js" type="text/javascript"></script>

	<!-- COLOR -->
	<link href="<?php echo URL_TPL; ?>css/ext/jquery-ui.css" rel="stylesheet" />
    <script src="<?php echo URL_TPL; ?>js/ext/jquery-ui.min.js"></script>
    <link href="<?php echo URL_TPL; ?>css/evol-colorpicker.min.css" rel="stylesheet" />
    <script src="<?php echo URL_TPL; ?>js/evol-colorpicker.min.js"></script>

	<script>
		$(document).ready(function(){

			function setColor(evt, color){
		        if(color){
		        	document.getElementById("color").value = color;
		            $('#name').css('color', color);
		        }
			}

			$('#evo-color').colorpicker({
				color: '#42ba96',
				customTheme: ['#DA4453', '#FCBB42', '#37BC9B', '#3BAFDA', '#4A89DC', '#967ADC', '#D770AD', '#AAB2BD', '#434A54'],
				transparentColor: false,
				strings: "Colores,Colores básicos,Más colores,Menos colores,Paleta,Historial,Aún no hay historial."
			}).on('change.color', setColor).on('mouseover.color', setColor);

			// Fix links
			$('a[href="#"]').attr('href', 'javascript:void(0)');

		});
    </script>

</html>