<?php
/**
 * Plugin Name: Graficador
 * Description: Generador d'imatges per xarxes socials (Portada + Resultats). Estil modern groc/negre.
 * Version: 1.2
 * Author: Albert Noe Noe
 */

// Evitem accés directe
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 1. Menú d'Administració (Generador + Configuració)
 */
add_action( 'admin_menu', function() {
	// Pàgina Principal (Generador)
	add_menu_page(
		'Graficador',
		'Graficador',
		'manage_options',
		'graficador',
		'sr_render_page_generator',
		'dashicons-format-gallery',
		99
	);

	// Submenú (Generador) - Perquè surti repetit com a primera opció
	add_submenu_page(
		'graficador',
		'Generador',
		'Generador',
		'manage_options',
		'graficador',
		'sr_render_page_generator'
	);

	// Submenú (Configuració)
	add_submenu_page(
		'graficador',
		'Configuració',     
		'Configuració',     
		'manage_options',
		'graficador-config',
		'sr_render_settings_page'
	);
});

/**
 * Pàgina de Configuració.
 */
function sr_render_settings_page() {
	 // Carreguem la llibreria per ordenar (Sortable) pròpia de WordPress
	 wp_enqueue_script('jquery-ui-sortable');
	 // Necessari per al gestor de mitjans (pujar imatges/svg)
	 wp_enqueue_media();
 
	 // Guardar dades
	 if ( isset($_POST['sr_save_logos']) && check_admin_referer('sr_save_logos_action', 'sr_save_logos_nonce') ) {
		 // 1. Guardar Logos Footer
		 $logos = isset($_POST['sr_logos']) ? array_map('esc_url_raw', $_POST['sr_logos']) : array();
		 update_option('sr_patrocinadors_footer', $logos);
 
		 // 2. Guardar Stickers (NOU)
		 $stickers = isset($_POST['sr_stickers']) ? array_map('esc_url_raw', $_POST['sr_stickers']) : array();
		 update_option('sr_stickers_list', $stickers);
 
		 // 3. Guardar Icones
		 $icon_schedule = isset($_POST['sr_url_icon_schedule']) ? esc_url_raw($_POST['sr_url_icon_schedule']) : '';
		 update_option('sr_url_icon_schedule', $icon_schedule);
 
		 $icon_results = isset($_POST['sr_url_icon_results']) ? esc_url_raw($_POST['sr_url_icon_results']) : '';
		 update_option('sr_url_icon_results', $icon_results);
 
		 // 4. Guardar Categories d'Escola
		 $escola_cats = isset($_POST['sr_escola_cats']) ? array_map('sanitize_text_field', $_POST['sr_escola_cats']) : array();
		 update_option('sr_escola_categories', $escola_cats);
 
		 echo '<div class="notice notice-success is-dismissible"><p>Configuració guardada correctament!</p></div>';
	 }
 
	 // Recuperar dades guardades
	 $saved_logos = get_option('sr_patrocinadors_footer', array());
	 $saved_stickers = get_option('sr_stickers_list', array()); // NOU
	 $saved_icon_schedule = get_option('sr_url_icon_schedule', '');
	 $saved_icon_results  = get_option('sr_url_icon_results', '');
	 $saved_escola_cats   = get_option('sr_escola_categories', array());
 
	 // --- LÒGICA PER XUCLAR CATEGORIES (AUTOMÀTIC) ---
	 $all_cats = array();
	 $terms = get_terms( array( 'taxonomy' => 'sr_category', 'hide_empty' => false ) );
	 if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		 foreach ( $terms as $term ) {
			 $all_cats[] = $term->name;
		 }
	 }
 
	 global $wpdb;
	 $meta_cats = $wpdb->get_col("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '_sr_categoria' AND meta_value != ''");
	 if($meta_cats) {
		 $all_cats = array_merge($all_cats, $meta_cats);
	 }
	 
	 $all_cats = array_unique($all_cats);
	 $all_cats = array_filter($all_cats);
	 sort($all_cats);
 
	 $cats_in_escola = array();
	 $cats_in_general = array();
 
	 foreach($all_cats as $cat_name) {
		 if(in_array($cat_name, $saved_escola_cats)) {
			 $cats_in_escola[] = $cat_name;
		 } else {
			 $cats_in_general[] = $cat_name;
		 }
	 }
	 ?>
	 <div class="wrap">
		 <h1>Configuració Graficador</h1>
		 
		 <form method="post">
			 <?php wp_nonce_field('sr_save_logos_action', 'sr_save_logos_nonce'); ?>
 
			 <div style="display:flex; flex-wrap:wrap; gap:20px;">
				 
				 <!-- COLUMNA ESQUERRA: Categories -->
				 <div class="card" style="flex: 2; min-width: 300px; padding: 20px;">
					 <h2>Distribució de Categories</h2>
					 <p>Arrossega les categories a la columna "Escola".</p>
					 
					 <div style="display:flex; gap:20px; margin-top:20px;">
						 <!-- Columna General -->
						 <div style="flex:1;">
							 <h3>General / Competició</h3>
							 <ul id="sortable-general" class="sr-cat-list">
								 <?php foreach($cats_in_general as $cat): ?>
									 <li class="ui-state-default"><?php echo esc_html($cat); ?>
										 <input type="hidden" value="<?php echo esc_attr($cat); ?>">
									 </li>
								 <?php endforeach; ?>
							 </ul>
						 </div>
 
						 <!-- Columna Escola -->
						 <div style="flex:1;">
							 <h3>Escola 🎓</h3>
							 <ul id="sortable-escola" class="sr-cat-list school-list">
								 <?php foreach($cats_in_escola as $cat): ?>
									 <li class="ui-state-default"><?php echo esc_html($cat); ?>
										 <input type="hidden" name="sr_escola_cats[]" value="<?php echo esc_attr($cat); ?>">
									 </li>
								 <?php endforeach; ?>
							 </ul>
						 </div>
					 </div>
				 </div>
 
				 <!-- COLUMNA DRETA: Icones, Stickers i Logos -->
				 <div style="flex: 1; min-width: 300px; display:flex; flex-direction:column; gap:20px;">
					 
					 <!-- SECCIÓ ICONES -->
					 <div class="card" style="padding: 20px;">
						 <h2>Icones (Superior)</h2>
						 <div style="margin-bottom: 20px;">
							 <label style="font-weight:bold; display:block; margin-bottom:5px;">Icona per HORARIS:</label>
							 <div style="display:flex; gap:10px; align-items:center;">
								 <input type="hidden" name="sr_url_icon_schedule" id="sr_url_icon_schedule" value="<?php echo esc_attr($saved_icon_schedule); ?>">
								 <div id="preview-icon-schedule" style="width:50px; height:50px; background:#f0f0f1; border:1px solid #ccc; display:flex; align-items:center; justify-content:center;">
									 <?php if($saved_icon_schedule): ?>
										 <img src="<?php echo esc_url($saved_icon_schedule); ?>" style="max-width:100%; max-height:100%;">
									 <?php else: ?>
										 <span class="dashicons dashicons-format-image" style="color:#ccc;"></span>
									 <?php endif; ?>
								 </div>
								 <button type="button" class="button btn-upload-icon" data-target="#sr_url_icon_schedule" data-preview="#preview-icon-schedule">Pujar</button>
								 <button type="button" class="button btn-remove-icon" data-target="#sr_url_icon_schedule" data-preview="#preview-icon-schedule" style="color:#a00;">&times;</button>
							 </div>
						 </div>
						 <div>
							 <label style="font-weight:bold; display:block; margin-bottom:5px;">Icona per RESULTATS:</label>
							 <div style="display:flex; gap:10px; align-items:center;">
								 <input type="hidden" name="sr_url_icon_results" id="sr_url_icon_results" value="<?php echo esc_attr($saved_icon_results); ?>">
								 <div id="preview-icon-results" style="width:50px; height:50px; background:#f0f0f1; border:1px solid #ccc; display:flex; align-items:center; justify-content:center;">
									 <?php if($saved_icon_results): ?>
										 <img src="<?php echo esc_url($saved_icon_results); ?>" style="max-width:100%; max-height:100%;">
									 <?php else: ?>
										 <span class="dashicons dashicons-format-image" style="color:#ccc;"></span>
									 <?php endif; ?>
								 </div>
								 <button type="button" class="button btn-upload-icon" data-target="#sr_url_icon_results" data-preview="#preview-icon-results">Pujar</button>
								 <button type="button" class="button btn-remove-icon" data-target="#sr_url_icon_results" data-preview="#preview-icon-results" style="color:#a00;">&times;</button>
							 </div>
						 </div>
					 </div>
 
					 <!-- SECCIÓ STICKERS (NOU) -->
					 <div class="card" style="padding: 20px;">
						 <h2>Stickers / Adhesius</h2>
						 <p>Puja elements gràfics (PNG transparent) per decorar.</p>
						 <div id="sr-stickers-wrapper" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; min-height: 50px;">
							 <?php if(!empty($saved_stickers)): ?>
								 <?php foreach($saved_stickers as $url): ?>
									 <div class="sr-logo-item" style="width:60px; height:60px; border:1px solid #ccc; position:relative; display:flex; align-items:center; justify-content:center; background:#fff;">
										 <input type="hidden" name="sr_stickers[]" value="<?php echo esc_attr($url); ?>">
										 <img src="<?php echo esc_url($url); ?>" style="max-width:50px; max-height:50px; pointer-events: none;">
										 <span class="sr-remove-logo" style="position:absolute; top:-5px; right:-5px; background:red; color:white; border-radius:50%; width:15px; height:15px; text-align:center; cursor:pointer; line-height:15px; font-size:10px; font-weight:bold;">&times;</span>
									 </div>
								 <?php endforeach; ?>
							 <?php endif; ?>
						 </div>
						 <button type="button" class="button" id="btn-add-sticker">Afegir Sticker</button>
					 </div>
		 
					 <!-- SECCIÓ LOGOS FOOTER -->
					 <div class="card" style="padding: 20px;">
						 <h2>Patrocinadors (Footer)</h2>
						 <p>Arrossega per ordenar.</p>
						 <div id="sr-logos-wrapper" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; min-height: 50px;">
							 <?php if(!empty($saved_logos)): ?>
								 <?php foreach($saved_logos as $url): ?>
									 <div class="sr-logo-item" style="width:60px; height:60px; border:1px solid #ccc; position:relative; display:flex; align-items:center; justify-content:center; background:#fff; cursor: move;">
										 <input type="hidden" name="sr_logos[]" value="<?php echo esc_attr($url); ?>">
										 <img src="<?php echo esc_url($url); ?>" style="max-width:50px; max-height:50px; pointer-events: none;">
										 <span class="sr-remove-logo" style="position:absolute; top:-5px; right:-5px; background:red; color:white; border-radius:50%; width:15px; height:15px; text-align:center; cursor:pointer; line-height:15px; font-size:10px; font-weight:bold;">&times;</span>
									 </div>
								 <?php endforeach; ?>
							 <?php endif; ?>
						 </div>
						 <button type="button" class="button" id="btn-add-logo">Afegir Logo Footer</button>
					 </div>
				 </div>
			 </div>
 
			 <hr>
			 <input type="submit" name="sr_save_logos" class="button button-primary button-large" value="Guardar Tots els Canvis">
		 </form>
	 </div>
 
	 <script>
	 jQuery(document).ready(function($){
		 // Ordenació
		 $('#sr-logos-wrapper').sortable({ placeholder: "ui-state-highlight-logo", forcePlaceholderSize: true });
		 $('#sr-stickers-wrapper').sortable({ placeholder: "ui-state-highlight-logo", forcePlaceholderSize: true });
 
		 $( "#sortable-general, #sortable-escola" ).sortable({
			 connectWith: ".sr-cat-list",
			 placeholder: "ui-state-highlight-cat",
			 receive: function(event, ui) {
				 var targetID = $(this).attr('id');
				 var $input = ui.item.find('input');
				 if(targetID === 'sortable-escola') {
					 $input.attr('name', 'sr_escola_cats[]');
					 ui.item.addClass('is-school');
				 } else {
					 $input.removeAttr('name');
					 ui.item.removeClass('is-school');
				 }
			 }
		 }).disableSelection();
 
		 // Gestió Icones Individuals
		 $('.btn-upload-icon').on('click', function(e){
			 e.preventDefault();
			 var btn = $(this);
			 var frame = wp.media({ title: 'Selecciona Icona', button: { text: 'Fer servir' }, multiple: false });
			 frame.on('select', function(){
				 var attachment = frame.state().get('selection').first().toJSON();
				 $(btn.data('target')).val(attachment.url);
				 $(btn.data('preview')).html('<img src="'+attachment.url+'" style="max-width:100%; max-height:100%;">');
			 });
			 frame.open();
		 });
 
		 $('.btn-remove-icon').on('click', function(e){
			 e.preventDefault();
			 var btn = $(this);
			 $(btn.data('target')).val('');
			 $(btn.data('preview')).html('<span class="dashicons dashicons-format-image" style="color:#ccc;"></span>');
		 });
 
		 // Gestió Logos Footer (Generic function for both logos and stickers)
		 function setupRepeater(btnId, wrapperId, inputName) {
			 let frame;
			 $(btnId).on('click', function(e){
				 e.preventDefault();
				 if(frame){ frame.open(); return; }
				 frame = wp.media({ title: 'Selecciona Imatge', button: { text: 'Afegir' }, multiple: false });
				 frame.on('select', function(){
					 let attachment = frame.state().get('selection').first().toJSON();
					 let html = `
						 <div class="sr-logo-item" style="width:60px; height:60px; border:1px solid #ccc; position:relative; display:flex; align-items:center; justify-content:center; background:#fff; cursor: move;">
							 <input type="hidden" name="${inputName}[]" value="${attachment.url}">
							 <img src="${attachment.url}" style="max-width:50px; max-height:50px; pointer-events: none;">
							 <span class="sr-remove-logo" style="position:absolute; top:-5px; right:-5px; background:red; color:white; border-radius:50%; width:15px; height:15px; text-align:center; cursor:pointer; line-height:15px; font-size:10px; font-weight:bold;">&times;</span>
						 </div>`;
					 $(wrapperId).append(html);
				 });
				 frame.open();
			 });
		 }
 
		 setupRepeater('#btn-add-logo', '#sr-logos-wrapper', 'sr_logos');
		 setupRepeater('#btn-add-sticker', '#sr-stickers-wrapper', 'sr_stickers');
		 
		 $(document).on('click', '.sr-remove-logo', function(){ $(this).parent().remove(); });
	 });
	 </script>
	 <style>
		 .sr-cat-list { border: 1px solid #ddd; background: #f9f9f9; min-height: 200px; list-style-type: none; margin: 0; padding: 10px; border-radius: 4px; }
		 .school-list { background: #e8f5e9; border-color: #c8e6c9; }
		 .sr-cat-list li { margin: 5px 0; padding: 8px 10px; font-size: 13px; background: #fff; border: 1px solid #ccc; border-radius: 3px; cursor: move; }
		 .ui-state-highlight-cat { height: 35px; background: #fff3cd; border: 1px dashed #ffc107; }
		 .ui-state-highlight-logo { height: 60px; width: 60px; background: #f0f0f1; border: 2px dashed #ccc; visibility: visible !important; }
	 </style>
	 <?php
 }

/**
 * 2. Renderitzat de la Pàgina (HTML + CSS + JS)
 */
/* -------------------------------------------------------------------------
	1. FUNCIÓ PRINCIPAL (CONTROLADOR)
	Aquesta és la funció que substitueix la teva sr_render_page_generator original.
	S'encarrega d'obtenir les dades i cridar a la resta de peces.
	------------------------------------------------------------------------- */
function sr_render_page_generator() {
		// A. Recuperem opcions
		$saved_logos = get_option('sr_patrocinadors_footer', array());
		$saved_stickers = get_option('sr_stickers_list', array()); 
		$saved_stickers_config = get_option('sr_stickers_global_config', array()); // NOU: Config guardada
		$url_icon_schedule = get_option('sr_url_icon_schedule', '');
		$url_icon_results  = get_option('sr_url_icon_results', '');
		$escola_categories = get_option('sr_escola_categories', array());
		$url_texture = plugin_dir_url(__FILE__) . 'textura.png';
	
		// B. Convertim a JSON
		$json_logos = json_encode($saved_logos);
		$json_stickers = json_encode($saved_stickers); 
		$json_stickers_config = json_encode($saved_stickers_config); // NOU
		$json_config = json_encode(array(
			'icon_schedule' => $url_icon_schedule,
			'icon_results'  => $url_icon_results,
			'escola_cats'   => $escola_categories,
			'texture_url'   => $url_texture
		));
	
		?>
		<div class="wrap sr-generator-wrap">
			<h1 class="wp-heading-inline" style="margin-bottom: 20px;">Graficador de Jornada</h1>
			
			<?php 
			// Barra d'eines
			sr_render_gen_toolbar($saved_logos); 
			
			// Zona Preview
			sr_render_gen_preview(); 
			?>
		</div>
	
		<?php 
		sr_render_gen_libs(); 
		sr_render_gen_css(); 
		sr_render_gen_js_utils(); 
		// Passem la nova variable al JS
		sr_render_gen_js_logic($json_config, $json_logos, $json_stickers, $json_stickers_config); 
	}
 
 /* -------------------------------------------------------------------------
	2. HTML: BARRA D'EINES (TOOLBAR)
	------------------------------------------------------------------------- */
 function sr_render_gen_toolbar($saved_logos) {
	 ?>
	 <!-- BARRA D'EINES PROFESSIONAL -->
	 <div class="sr-toolbar">
		 
		 <!-- GRUP 1: DATES -->
		 <div class="sr-tool-group">
			 <div class="sr-label"><span class="dashicons dashicons-calendar-alt"></span> Dates</div>
			 <div class="sr-inputs-row">
				 <input type="date" id="dateStart" value="<?php echo date('Y-m-d', strtotime('last saturday')); ?>" title="Data Inici">
				 <span class="sr-separator">➜</span>
				 <input type="date" id="dateEnd" value="<?php echo date('Y-m-d', strtotime('next sunday')); ?>" title="Data Fi">
			 </div>
		 </div>
 
		 <!-- GRUP 2: FORMAT I FONS -->
		 <div class="sr-tool-group">
			 <div class="sr-label"><span class="dashicons dashicons-art"></span> Estil</div>
			 <div class="sr-inputs-row">
				 <!-- Switch Format -->
				 <div class="sr-segmented-control">
					 <button id="btnFmtPortrait" class="active">Portrait (4:5)</button>
					 <button id="btnFmtStory">Story (9:16)</button>
				 </div>
				 
				 <!-- Input Fons -->
				 <div class="sr-bg-input-wrapper">
					 <input type="text" id="coverImageUrl" placeholder="URL Fons base...">
					 <button id="btnUploadCover" title="Pujar Imatge"><span class="dashicons dashicons-upload"></span></button>
				 </div>
			 </div>
		 </div>
 
		 <!-- GRUP 3: ACCIONS PRINCIPALS -->
		 <div class="sr-tool-group actions">
			 <button id="btnLoadData" class="sr-btn-hero primary">
				 <span class="dashicons dashicons-update"></span>
				 <span>Carregar Dades</span>
			 </button>
			 <button id="btnDownloadAll" class="sr-btn-hero secondary" style="display:none;">
				 <span class="dashicons dashicons-download"></span>
				 <span>Descarregar ZIP</span>
			 </button>
		 </div>
	 </div>
	 
	 <?php if(empty($saved_logos)): ?>
		 <div class="notice notice-warning inline" style="margin-bottom:20px;"><p>Nota: No has configurat cap logo al footer. Ves a "Configuració".</p></div>
	 <?php endif; ?>
	 <?php
 }
 
 /* -------------------------------------------------------------------------
	3. HTML: ÀREA DE PREVISUALITZACIÓ I LOADERS
	------------------------------------------------------------------------- */
 function sr_render_gen_preview() {
	 ?>
	 <div id="loading" style="display:none;">
		 <span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span> Carregant dades de l'API...
	 </div>
	 
	 <div id="zip-loading" style="display:none; background:#e6f7ff; color:#0073aa; padding:15px; border:1px solid #b3e0ff; border-radius:4px; margin-bottom:20px; align-items:center; gap:10px;">
		 <span class="dashicons dashicons-hourglass" style="font-size:20px;"></span> 
		 <strong>Processant:</strong> Generant imatges i comprimint ZIP... Si us plau espera.
	 </div>
 
	 <!-- ZONA DE PREVISUALITZACIÓ -->
	 <div id="preview-area"></div>
	 <?php
 }
 
 /* -------------------------------------------------------------------------
	4. LLIBRERIES EXTERNES (CSS I JS)
	------------------------------------------------------------------------- */
 function sr_render_gen_libs() {
	 ?>
	 <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,400&display=swap" rel="stylesheet">
	 <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
	 <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
	 <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
	 <?php
 }
 
 /* -------------------------------------------------------------------------
	5. ESTILS CSS
	------------------------------------------------------------------------- */
function sr_render_gen_css() {
		?>
		<style>
			.sr-generator-wrap { font-family: 'Montserrat', sans-serif; box-sizing: border-box; }
			
			/* --- TOOLBAR --- */
			.sr-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px; background: #fff; padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); margin-bottom: 30px; border: 1px solid #e0e0e0; }
			.sr-tool-group { display: flex; flex-direction: column; gap: 6px; }
			.sr-tool-group.actions { margin-left: auto; flex-direction: row; align-items: flex-end; gap: 10px; }
			.sr-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #888; display: flex; align-items: center; gap: 5px; letter-spacing: 0.5px; }
			.sr-label .dashicons { font-size: 14px; width: 14px; height: 14px; color: #aaa; }
			.sr-inputs-row { display: flex; align-items: center; gap: 10px; }
			.sr-inputs-row input[type="date"] { border: 1px solid #ddd; background: #f9f9f9; border-radius: 4px; padding: 0 10px; height: 36px; font-size: 13px; color: #333; }
			.sr-segmented-control { display: flex; background: #eee; padding: 3px; border-radius: 6px; height: 36px; }
			.sr-segmented-control button { border: none; background: transparent; padding: 0 15px; font-size: 12px; font-weight: 600; color: #666; cursor: pointer; border-radius: 4px; height: 100%; display: flex; align-items: center; }
			.sr-segmented-control button.active { background: #fff; color: #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
			.sr-bg-input-wrapper { display: flex; align-items: center; gap: 0; }
			.sr-bg-input-wrapper input { border: 1px solid #ddd; border-right: none; border-radius: 4px 0 0 4px; height: 36px; padding: 0 10px; width: 180px; font-size: 12px; }
			.sr-bg-input-wrapper button { border: 1px solid #ddd; background: #f0f0f1; height: 36px; width: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; }
			.sr-btn-hero { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 40px; padding: 0 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
			.sr-btn-hero.primary { background: #2271b1; color: #fff; }
			.sr-btn-hero.secondary { background: #fff; color: #2271b1; border: 1px solid #2271b1; }
	
			/* --- PREVIEW AREA --- */
			#preview-area { display: flex; flex-wrap: wrap; gap: 40px; justify-content: center; background: #f0f0f1; padding: 40px; border-radius: 4px; }
			
			.sr-slide-wrapper { transition: transform 0.2s, box-shadow 0.2s; border: 4px solid transparent; border-radius: 4px; position: relative; }
			.sr-slide-wrapper.is-selected { border-color: #00a32a; box-shadow: 0 0 15px rgba(0, 163, 42, 0.3); transform: scale(1.02); }
			
			.sr-slide { width: 540px; height: 675px; background-color: #FFD700; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; display: flex; flex-direction: column; color: #000; transform-origin: top left; transition: height 0.3s ease; user-select: none; }
			.sr-slide.style-horaris { background-color: #FFFFFF !important; }
			.sr-slide.format-story { height: 960px; }
	
			/* LAYERS */
			.sr-bg-container { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; overflow: hidden; mix-blend-mode: multiply; opacity: 0.15; }
			.sr-bg-img { width: 300%; height: 300%; position: absolute; left: -100%; top: -100%; background-size: auto; background-position: center; background-repeat: no-repeat; transition: background-image 0.2s; }
			.sr-slide:not(.style-horaris) #slide-0 .sr-bg-container { opacity: 1; }
			.sr-slide.style-horaris .sr-bg-container { mix-blend-mode: normal !important; opacity: 1 !important; }
			.sr-texture-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 5; pointer-events: none; background-repeat: no-repeat; background-size: auto; auto; opacity: 0.35; }
			.sr-slide.format-story .sr-texture-overlay { 
				background-repeat: repeat-y !important; /* Es repeteix a sota */
				background-size: auto !important;       /* Mida ORIGINAL exacta (evita que s'estrenyi) */
				background-position: top center !important; 
			}
			#slide-0 .sr-texture-overlay { opacity: 0.35 !important; }
			
			/* --- CAPES STICKERS I CONTINGUT --- */
			
			/* Z-INDEX BASE (Mode Normal / Exportació) */
			.sr-stickers-layer-bottom { position: absolute; top:0; left:0; width:100%; height:100%; z-index: 8; pointer-events: none; }
			.sr-content-layer { position: relative; z-index: 10; width: 100%; height: 100%; display: flex; flex-direction: column; pointer-events: none; }
			.sr-stickers-layer-top { position: absolute; top:0; left:0; width:100%; height:100%; z-index: 50; pointer-events: none; }
	
			/* Elements interactius del contingut */
			.sr-cover-content, .sr-page-header, .sr-match-list, .sr-pagination-wrapper, .sr-footer-container { pointer-events: auto; }
			.sr-slide.format-story .sr-content-layer { justify-content: center; }
	
			/* --- MODES DE TREBALL --- */
			
			/* MODE TEXT: El text mana. */
			.sr-slide-wrapper.mode-text .sr-content-layer { opacity: 1; }
	
			/* MODE STICKERS: 
			   1. Text semi-transparent i no clickable.
			   2. Capa Bottom PUJA temporalment sobre el text (z-index 15) per poder clicar-la.
			*/
			.sr-slide-wrapper.mode-stickers .sr-content-layer { opacity: 0.4; pointer-events: none !important; }
			
			.sr-slide-wrapper.mode-stickers .sr-cover-content,
			.sr-slide-wrapper.mode-stickers .sr-match-list,
			.sr-slide-wrapper.mode-stickers .sr-footer-container { pointer-events: none !important; }
	
			.sr-slide-wrapper.mode-stickers .sr-stickers-layer-bottom { 
				z-index: 15 !important; 
				pointer-events: none; 
			}
			
			/* Assegurem que els stickers (items) sempre pillen el clic */
			.sr-slide-wrapper.mode-stickers .sr-stickers-layer-bottom .sr-sticker-item,
			.sr-slide-wrapper.mode-stickers .sr-stickers-layer-top .sr-sticker-item { 
				pointer-events: auto !important; 
			}
	
			/* MODE IMAGE: */
			.sr-slide-wrapper.mode-image .sr-content-layer { pointer-events: none; opacity: 0.3; filter: blur(1px); }
			.sr-slide-wrapper.mode-image .sr-bg-container { pointer-events: auto; cursor: grab; outline: 2px dashed rgba(0,0,0,0.2); z-index: 20; }
			
			/* --- ITEMS STICKER --- */
			.sr-sticker-item {
				position: absolute; display: inline-block; cursor: grab; pointer-events: auto;
				user-select: none; transform-origin: center center;
			}
			.sr-sticker-item:active { cursor: grabbing; }
			.sr-sticker-item img { display: block; width: 100%; height: 100%; object-fit: contain; pointer-events: none; }
			
			.sr-sticker-item.is-active {
				outline: 2px dashed #2271b1;
				z-index: 1000 !important; 
				filter: drop-shadow(0 0 5px rgba(255,255,255,0.8));
			}
	
			.sr-slide.format-story .sr-cover-content { padding-top: 100px; }
			
			/* Resta d'elements visuals... */
			.sr-cover-content { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; }
			.sr-title-box { text-align: center; display: flex; flex-direction: column; gap: 5px; align-items: center; margin-bottom: 50px; }
			.sr-title-block { display: inline-block; padding: 5px 30px; font-size: 55px; line-height: 1; font-weight: 700; text-transform: uppercase; box-shadow: 10px 10px 0px rgba(0,0,0,0.1); transform: skew(-10deg); }
			.sr-date-block { display: inline-block; padding: 8px 30px; font-size: 24px; font-weight: 500; text-transform: none; margin-top: 10px; transform: skew(-10deg); }
			.sr-title-block span, .sr-date-block span { transform: skew(10deg); display:inline-block; }
			.sr-arrow-cover { position: absolute; bottom: 118px; right: 52px; font-size: 55px; color: #000; transform: scaleY(1.2) translateY(-5px); line-height: 1; }
			
			.sr-slide:not(.style-horaris) .sr-title-block { background: #ffffffd9; color: #000; }
			.sr-slide:not(.style-horaris) .sr-date-block { background: #000; color: #fff; }
			.sr-slide:not(.style-horaris) .sr-ph-title { background: #ffffffd9; color: #000; }
			.sr-slide.style-horaris .sr-title-block { background: #FFD700; color: #000; }
			.sr-slide.style-horaris .sr-date-block { color: #fff; }
			.sr-slide.style-horaris .sr-ph-title { background: #FFD700; color: #000; }
	
			.sr-pagination-wrapper { position: absolute; bottom: 35px; right: 21px; display: flex; align-items: center; justify-content: flex-end; opacity: 0.9; }
			.sr-slide.format-story .sr-pagination-wrapper { bottom: 150px; }
			.sr-pagination-num { font-size: 40px; line-height: 1; display: inline-block; font-weight: 100; font-style: italic; }
			.sr-pagination-num b { font-weight: 900; }
			.sr-arrow-interior { font-size: 35px; font-weight: 400; line-height: 0.8; transform: translateY(-6px) scaleY(1.2); display: inline-block; }
	
			.sr-footer-container { position: absolute; bottom: 0; width: 100%; height: 80px; z-index: 30; }
			.sr-slide.format-story .sr-footer-container { bottom: 150px; height: 80px !important; overflow: hidden !important; }
			.sr-footer-slant { position: absolute; bottom: -58px; width: 457px; height: 131px; left: 47%; margin-left: -230px; transform: skew(-10deg); transform-origin: bottom center; }
			.sr-slide:not(.style-horaris) .sr-footer-slant { background: white; }
			.sr-slide.style-horaris .sr-footer-slant { background: #FFD700; }
			.sr-footer-logos { position: absolute; bottom: 0; width: 460px; height: 80px; left: 48.4%; margin-left: -226px; bottom: -3px; display: flex; align-items: center; justify-content: center; gap: 20px; padding: 0 10px; }
			.sr-footer-logos img { max-height: 28px; width: auto; max-width: 100%; object-fit: contain; flex-shrink: 1; margin-top: 5px; }
	
			.sr-page-header { text-align: center; padding-top: 50px; padding-bottom: 24px; }
			.sr-slide.format-story .sr-page-header { padding-top: 180px; }
			.sr-ph-super { font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 5px; min-height: 16px; }
			.sr-ph-super.school-mode { font-size: 16px; font-weight: 900; letter-spacing: 1px; margin-bottom: 0px; margin-right: 120px; font-style: italic; background: #000; color: #fff; padding: 4px 15px; display: inline-block; transform: skew(-10deg); box-shadow: 3px 3px 0px rgba(0, 0, 0, 0.1); }
			.sr-ph-title { font-size: 39px; font-weight: 700; text-transform: uppercase; padding: 12px 20px; display: inline-block; transform: skew(-10deg); }
			.sr-ph-date { font-size: 20px; margin-top: 10px; font-weight: 500; }
			
			.sr-match-list { display: flex; flex-direction: column; gap: 12px; padding: 0 15px; margin-top: 10px; flex-grow: 1; }
			.sr-match-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 10px;	 position: relative; transform: skew(-10deg); }
			.sr-slide:not(.style-horaris) .sr-match-row { background: rgba(255,255,255, 0.4); }
			.sr-slide.style-horaris .sr-match-row { background: rgb(202 200 200 / 66%); }
			.sr-team { flex: 1; display: flex; align-items: center; font-size: 13px; font-weight: 500; text-transform: uppercase; line-height: 1.1; }
			.sr-team.local { justify-content: flex-end; text-align: right; }
			.sr-team.visit { justify-content: flex-start; text-align: left; }
			.sr-team-logo { width: 30px; height: 30px; object-fit: contain; margin: 0 8px; transform: skew(+10deg); }
			.sr-score-box { font-size: 21px; font-weight: 600; padding: 8px 15px; min-width: 60px; text-align: center; transform: skew(-3deg); margin: 0 10px; display: flex; justify-content: center; align-items: center; }
			.sr-slide:not(.style-horaris) .sr-score-box { background: #000; color: #fff; }
			.sr-slide.style-horaris .sr-score-box { background: #000; color: #fff; }
			.sr-score-box span { transform: skew(10deg); }
			.sr-score-box.ajornat { background: transparent !important; color: #d00 !important; font-size: 16px; transform: skew(10deg); }
			.icon-top-left { position: absolute; top: 12px; left: 14px; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; z-index: 99; pointer-events: none; }
			.sr-slide.format-story .icon-top-left { top: 140px; }
			.icon-top-left img { width: 100%; height: 100%; object-fit: contain; }
	
			[contenteditable]:hover { outline: 2px dashed #000; cursor: text; }
			
			/* UI CONTROLS */
			.sr-actions { margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #ddd; width: 540px; display: flex; flex-direction: column; gap: 10px; box-sizing: border-box; }
			.sr-actions-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
			.sr-mode-switcher { display: flex; gap: 0; background: #eee; border-radius: 4px; padding: 2px; }
			.sr-mode-btn { border: none; background: none; padding: 5px 10px; cursor: pointer; font-size: 12px; border-radius: 3px; display: flex; align-items: center; gap: 4px;}
			.sr-mode-btn.active { background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); font-weight: bold; color: #000; }
			.sr-zoom-control { display: flex; align-items: center; gap: 5px; flex-grow: 1; font-size: 12px; opacity: 0.5; pointer-events: none; transition: opacity 0.3s;}
			.sr-zoom-control.active { opacity: 1; pointer-events: auto; }
			.sr-zoom-control input { flex-grow: 1; }
			.btn-apply-all { display: flex; align-items: center; justify-content: center; gap: 5px; font-size: 11px !important; padding: 0 8px !important; height: 30px; }
			.sr-select-label { display: flex; align-items: center; gap: 5px; font-size: 12px; font-weight: bold; background: #f0f0f1; padding: 5px 10px; border-radius: 4px; border: 1px solid #ccc; cursor: pointer; user-select: none; }
			.sr-select-label.active-check { background: #d4edda; border-color: #c3e6cb; color: #155724; }
	
			/* MODAL PICKER */
			#sticker-picker-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index: 99999; display:flex; justify-content:center; align-items:center; }
			#sticker-picker-box { background:#fff; width: 500px; max-width:90%; padding:20px; border-radius:8px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); max-height: 80vh; overflow-y:auto; }
			.sticker-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin-top:15px; }
			.sticker-grid-item { border: 1px solid #eee; padding: 5px; cursor: pointer; transition: 0.2s; }
			.sticker-grid-item:hover { background: #f0f6fc; border-color: #2271b1; }
			.sticker-grid-item img { width: 100%; height: auto; display: block; }
	
			/* SIDE MENU */
			.sr-sticker-side-menu { position: absolute; top: 0; left: -260px; width: 240px; background: #fff; border: 1px solid #ccc; box-shadow: 3px 3px 15px rgba(0,0,0,0.1); padding: 15px; border-radius: 6px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; }
			.sr-sticker-side-menu:after { content: ""; position: absolute; top: 20px; right: -8px; width: 0; height: 0; border-top: 8px solid transparent; border-bottom: 8px solid transparent; border-left: 8px solid #fff; }
			.sr-sticker-side-menu h4 { margin: 0 0 10px 0; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
			.sticker-ctrl-row { display: flex; align-items: center; gap: 10px; font-size: 12px; }
			.sticker-ctrl-row label { width: 55px; font-weight: 600; color: #444; }
		</style>
		<?php
	}
 
 /* -------------------------------------------------------------------------
	6. FUNCIONS UTILITAT JS (PURES - SENSE DEPENDÈNCIES JQUERY/DOM)
	------------------------------------------------------------------------- */
 function sr_render_gen_js_utils() {
	 ?>
	 <script>
	 // --- UTILS PER NOMS D'ARXIU ---
	 function pad(num) { return num.toString().padStart(2, '0'); }
 
	 // --- UTILS PER PROCESSAMENT D'IMATGES (Async) ---
	 async function sr_generar_byn_contrast_dataurl(urlOriginal, ajustos) {
		   try {
			   if (!urlOriginal) return urlOriginal;
	   
			   const a = Object.assign({
				   contrast: 1.2,
				   gamma: 1.0,
				   brightness: 0,
				   blackBoost: 0,
				   bn: true,
				   saturation: 1.0 // 1.0 = Color Normal, 0.0 = Blanc i Negre
			   }, ajustos || {});
	   
			   const imatge = await new Promise((resolve, reject) => {
				   const im = new Image();
				   im.crossOrigin = "Anonymous"; 
				   im.onload = () => resolve(im);
				   im.onerror = reject;
				   im.src = urlOriginal;
			   });
	   
			   const canvas = document.createElement('canvas');
			   canvas.width = imatge.naturalWidth || imatge.width;
			   canvas.height = imatge.naturalHeight || imatge.height;
	   
			   const ctx = canvas.getContext('2d');
			   ctx.imageSmoothingEnabled = true;
			   ctx.imageSmoothingQuality = 'high';
			   ctx.drawImage(imatge, 0, 0);
	   
			   const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
			   const d = imgData.data;
			   const clamp = (v, min, max) => Math.max(min, Math.min(max, v));
	   
			   for (let i = 0; i < d.length; i += 4) {
				   let r = d[i], g = d[i + 1], b = d[i + 2];
				   
				   if (a.bn) {
					   // MODE CLÀSSIC (BLANC I NEGRE PUR)
					   let y = 0.2126 * r + 0.7152 * g + 0.0722 * b;
					   y = (y - 128) * a.contrast + 128;
					   y = y + a.brightness;
					   if (y < 140) y -= a.blackBoost;
					   y = 255 * Math.pow(clamp(y / 255, 0, 1), a.gamma);
					   y = clamp(y, 0, 255);
					   d[i] = y; d[i + 1] = y; d[i + 2] = y;
				   } else {
					   // MODE COLOR (AMB BRILLO I SATURACIÓ)
					   
					   // 1. Contrast
					   r = (r - 128) * a.contrast + 128;
					   g = (g - 128) * a.contrast + 128;
					   b = (b - 128) * a.contrast + 128;
					   
					   // 2. Brillo
					   r += a.brightness;
					   g += a.brightness;
					   b += a.brightness;
					   
					   // 3. Gamma
					   r = 255 * Math.pow(clamp(r / 255, 0, 1), a.gamma);
					   g = 255 * Math.pow(clamp(g / 255, 0, 1), a.gamma);
					   b = 255 * Math.pow(clamp(b / 255, 0, 1), a.gamma);
 
					   // 4. Saturació (Rentar el color)
					   if (a.saturation !== 1) {
						   let gray = 0.2126 * r + 0.7152 * g + 0.0722 * b;
						   r = gray + (r - gray) * a.saturation;
						   g = gray + (g - gray) * a.saturation;
						   b = gray + (b - gray) * a.saturation;
					   }
					   
					   d[i] = clamp(r, 0, 255);
					   d[i+1] = clamp(g, 0, 255);
					   d[i+2] = clamp(b, 0, 255);
				   }
			   }
			   ctx.putImageData(imgData, 0, 0);
			   return canvas.toDataURL('image/png');
		   } catch (e) {
			   return urlOriginal; 
		   }
	 }
	 
	 async function sr_generar_groc_negre_dataurl(urlImatge, ajustos) {
		 try {
			 if (!urlImatge) return urlImatge;
			 const a = Object.assign({}, {
				 contrast: 1.35, gamma: 0.60, brightness: 58, blackBoost: 3,
				 grocMinim: 0.18, grocPunch: 1.00
			 }, ajustos || {});
			 const ja_processada = !!a.ja_processada;
			 const groc = { r: 255, g: 215, b: 0 }; 
			 const imatge = await new Promise((resolve, reject) => {
				 const im = new Image();
				 im.crossOrigin = "Anonymous"; 
				 im.onload = () => resolve(im);
				 im.onerror = reject;
				 im.src = urlImatge;
			 });
			 const canvas = document.createElement('canvas');
			 canvas.width = imatge.naturalWidth || imatge.width;
			 canvas.height = imatge.naturalHeight || imatge.height;
			 const ctx = canvas.getContext('2d');
			 ctx.drawImage(imatge, 0, 0);
			 const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
			 const d = imgData.data;
			 const clamp = (v, min, max) => Math.max(min, Math.min(max, v));
			 for (let i = 0; i < d.length; i += 4) {
				 let r = d[i], g = d[i + 1], b = d[i + 2];
				 let y = 0.2126 * r + 0.7152 * g + 0.0722 * b;
				 if (!ja_processada) {
					 y = (y - 128) * a.contrast + 128;
					 y = y + a.brightness;
					 if (y < 140) y -= a.blackBoost;
					 y = 255 * Math.pow(clamp(y / 255, 0, 1), a.gamma);
					 y = clamp(y, 0, 255);
				 } else { y = clamp(y, 0, 255); }
				 let t = clamp(y / 255, 0, 1);
				 if(a.grocPunch !== 1) { t = Math.pow(t, 1 / a.grocPunch); }
				 const t2 = a.grocMinim + (t * (1 - a.grocMinim));
				 d[i] = groc.r * t2; d[i + 1] = groc.g * t2; d[i + 2] = groc.b * t2;
			 }
			 ctx.putImageData(imgData, 0, 0);
			 return canvas.toDataURL('image/png');
		 } catch (e) { return urlImatge; }
	 }
 
	 // --- UTILS PER EXPORTACIÓ (SUPERSAMPLING I FIXES) ---
	 function sr_canvas_supersampling_a_mida(canvasGran, ampleFinal, altFinal) {
		 // Creem un canvas final amb la mida exacta que volem descarregar
		 const canvasFinal = document.createElement('canvas');
		 canvasFinal.width = ampleFinal;
		 canvasFinal.height = altFinal;
	 
		 const ctxFinal = canvasFinal.getContext('2d');
	 
		 // Activem suavitzat d'imatge d'alta qualitat per una reducció neta
		 ctxFinal.imageSmoothingEnabled = true;
		 ctxFinal.imageSmoothingQuality = 'high';
	 
		 // Dibuixem el canvas gran dins del canvas final (reducció)
		 ctxFinal.drawImage(canvasGran, 0, 0, ampleFinal, altFinal);
	 
		 return canvasFinal;
	 }
	 
	 function sr_export_swap_bg_a_img(elementSlide) {
		 // Aquest helper depèn de jQuery, però s'executa quan jQuery ja està carregat
		 try {
			 const $slide = jQuery(elementSlide);
			 const $bgImgDiv = $slide.find('.sr-bg-img').first();
			 if (!$bgImgDiv.length) return null;
	 
			 const bg = $bgImgDiv[0].style.backgroundImage || '';
			 // FIX: Neteja d'URL més segura (evita errors amb cometes o espais)
			 let src = bg.replace(/^url\(['"]?/, '').replace(/['"]?\)$/, '');
			 if (!src || src === 'none' || src === 'undefined') return;
	 
			 // Guardem estat per restaurar
			 const estat = {
				 bg_original: $bgImgDiv[0].style.backgroundImage || '',
				 disp_original: $bgImgDiv[0].style.display || '',
				 will_original: $bgImgDiv[0].style.willChange || '',
				 transform_original: $bgImgDiv.css('transform') || 'none'
			 };
	 
			 const $bgContainer = $slide.find('.sr-bg-container').first();
			 if (!$bgContainer.length) return null;
	 
			 // Assegurem posicio relativa per poder centrar l'img amb left/top
			 const pos = $bgContainer.css('position');
			 if (!pos || pos === 'static') $bgContainer.css('position', 'relative');
	 
			 // Dimensions del "marc" (el teu slide: 540x675 o 540x960)
			 const ampleMarc = $bgContainer[0].clientWidth;
			 const altMarc = $bgContainer[0].clientHeight;
	 
			 // Creem <img> (però NO posem width/height 100% per no estirar)
			 const img = document.createElement('img');
			 img.crossOrigin = 'anonymous';
	 
			 img.style.position = 'absolute';
			 img.style.zIndex = '2';
			 img.style.pointerEvents = 'none';
	 
			 // ✅ La transformacio de l'usuari (translate/scale) l'apliquem igual
			 img.style.transformOrigin = 'center center';
			 img.style.transform = estat.transform_original;
	 
			 // Neteja per evitar que el background interfereixi
			 $bgImgDiv[0].style.backgroundImage = 'none';
			 $bgImgDiv[0].style.willChange = 'auto';
	 
			 // Inserim i després calculem el "contain" real quan la imatge carregui
			 $bgContainer[0].appendChild(img);
	 
			 // Assignem src al final per assegurar que ja està al DOM
			 img.onload = function() {
				 try {
					 const iw = img.naturalWidth || 1;
					 const ih = img.naturalHeight || 1;
	 
					 // ✅ Calcul "contain" exacte (com background-size: contain)
					 const escala = Math.max(ampleMarc / iw, altMarc / ih);
					 const ampleFit = Math.round(iw * escala);
					 const altFit = Math.round(ih * escala);
	 
					 const left = Math.round((ampleMarc - ampleFit) / 2);
					 const top = Math.round((altMarc - altFit) / 2);
	 
					 img.style.width = ampleFit + 'px';
					 img.style.height = altFit + 'px';
					 img.style.left = left + 'px';
					 img.style.top = top + 'px';
				 } catch (e) {}
			 };
	 
			 img.onerror = function() {
				 // Si falla, no fem res (html2canvas tirara igual)
			 };
	 
			 img.src = src;
	 
			 return function sr_restaurar() {
				 try { img.remove(); } catch(e) {}
	 
				 $bgImgDiv[0].style.backgroundImage = estat.bg_original;
				 $bgImgDiv[0].style.display = estat.disp_original;
				 $bgImgDiv[0].style.willChange = estat.will_original;
			 };
		 } catch (e) {
			 return null;
		 }
	 }	
	 
	 function sr_export_forcar_mode_text($wrapper) {
		 const teniaModeImage = $wrapper.hasClass('mode-image');
		 if (teniaModeImage) {
			 $wrapper.removeClass('mode-image').addClass('mode-text');
		 }
		 return function sr_restaurar_mode() {
			 if (teniaModeImage) {
				 $wrapper.removeClass('mode-text').addClass('mode-image');
			 }
		 };
	 }
	 </script>
	 <?php
 }
 
 /* -------------------------------------------------------------------------
	7. LÒGICA PRINCIPAL JS (EVENTS I GENERACIÓ)
	------------------------------------------------------------------------- */
function sr_render_gen_js_logic($json_config, $json_logos, $json_stickers, $json_stickers_config) {
		?>
		<script>
		jQuery(document).ready(function($) {
			const API_URL = '/wp-json/sr/v1/full-data';
			const SAVED_LOGOS = <?php echo $json_logos; ?>;
			const AVAILABLE_STICKERS = <?php echo $json_stickers; ?>;
			const SAVED_CONFIG = <?php echo $json_stickers_config; ?> || {}; 
			const CONFIG = <?php echo $json_config; ?>;
			
			let FOOTER_HTML = '';
			if(SAVED_LOGOS && SAVED_LOGOS.length > 0) {
				SAVED_LOGOS.forEach(function(url){
					FOOTER_HTML += `<img src="${url}" crossorigin="anonymous" alt="Logo">`;
				});
			} else {
				FOOTER_HTML = '<span style="font-size:10px; opacity:0.5">Configura els logos al menú Configuració</span>';
			}
	
			// ==========================================
			//  VARIABLES GLOBALS
			// ==========================================
			const SR_PORTADA_AJUSTOS_DEFAULT = {
				contrast: 1.35, gamma: 0.60, brightness: 58, blackBoost: 3,
				grocMinim: 0.18, grocPunch: 1.00
			};
			
			let SR_PORTADA_AJUSTOS = Object.assign({}, SR_PORTADA_AJUSTOS_DEFAULT);
			let SR_PORTADA_LAST_BN = null;
			let sr_tune_timer = null;
	
			// ==========================================
			//  STICKER SYSTEM (3-MODE SYSTEM + PERSISTÈNCIA)
			// ==========================================
			
			let activeSticker = null;
			let stickerTargetSlideId = null; 
	
			// HELPER: Activar Mode Sticker al wrapper
			function activateStickerMode($wrapper) {
				$wrapper.removeClass('mode-text mode-image').addClass('mode-stickers');
				$wrapper.find('.sr-mode-btn').removeClass('active');
				$wrapper.find('.sr-mode-btn[data-mode="stickers"]').addClass('active');
				// Amagar controls zoom imatge fons
				$wrapper.find('.sr-zoom-control').removeClass('active');
			}
	
			// HELPER: Guardar a BD (Reutilitzable)
			function sr_perform_save_stickers($slide, slideType, isSilent) {
				let stickersToSave = [];
				
				// Recollir TOTS els stickers d'aquesta slide
				$slide.find('.sr-sticker-item').each(function(){
					const $el = $(this);
					const p = $el.data('props');
					const url = $el.find('img').attr('src');
					let data = Object.assign({}, p, { url: url });
					stickersToSave.push(data);
				});
	
				$.ajax({
					url: ajaxurl, 
					method: 'POST',
					data: { action: 'sr_save_stickers_global', slide_type: slideType, stickers: stickersToSave },
					success: function(res) {
						if(res.success) {
							SAVED_CONFIG[slideType] = stickersToSave;
							if(!isSilent) alert("✅ Guardat correctament!");
							else console.log("Stickers guardats automàticament.");
						} else { 
							if(!isSilent) alert("❌ Error guardant: " + res.data); 
						}
					},
					error: function() { 
						if(!isSilent) alert("❌ Error de connexió amb el servidor."); 
					}
				});
			}
	
			// 1. OBRIR MODAL
			// (Funcionalitat integrada en el botó de mode)
			function openStickerPicker(slideId) {
				if(AVAILABLE_STICKERS.length === 0) { alert("No hi ha stickers configurats! Ves a Configuració."); return; }
				
				stickerTargetSlideId = slideId;
				
				// Si hi havia un menú lateral obert, el tanquem
				deselectAllStickers();
	
				let html = `
					<div id="sticker-picker-overlay">
						<div id="sticker-picker-box">
							<h2 style="margin-top:0;">Tria un Sticker</h2>
							<p>S'afegirà a la diapositiva.</p>
							<div class="sticker-grid">`;
				AVAILABLE_STICKERS.forEach(url => {
					html += `<div class="sticker-grid-item" data-url="${url}"><img src="${url}"></div>`;
				});
				html += `   </div>
							<button class="button" id="btnCloseStickerPicker" style="margin-top:20px;">Cancel·lar</button>
						</div>
					</div>`;
				$('body').append(html);
			}
	
			$(document).on('click', '#btnCloseStickerPicker, #sticker-picker-overlay', function(e){
				if(e.target === this) $('#sticker-picker-overlay').remove();
			});
	
			// 2. AFEGIR STICKER (CLIC)
			$(document).on('click', '.sticker-grid-item', function(){
				let url = $(this).data('url');
				$('#sticker-picker-overlay').remove();
				
				if(!stickerTargetSlideId) return;
				let $slide = $(stickerTargetSlideId);
				if(!$slide.length) return;
	
				addStickerToSlide($slide, url);
			});
	
			function addStickerToSlide($slide, url, props = null) {
				const defaults = {
					top: '50%', left: '50%', width: '150px', 
					rotation: 0, scale: 1, flip: 1, layer: 'top'
				};
				const p = Object.assign({}, defaults, props);
	
				const $sticker = $(`
					<div class="sr-sticker-item" style="top:${p.top}; left:${p.left}; width:${p.width}; z-index:100;">
						<img src="${url}" crossorigin="anonymous">
					</div>
				`);
				
				updateStickerVisuals($sticker, p);
	
				let containerClass = (p.layer === 'top') ? '.sr-stickers-layer-top' : '.sr-stickers-layer-bottom';
				$slide.find(containerClass).append($sticker);
	
				// Si és nou (no càrrega automàtica), seleccionem-lo
				if(!props) selectSticker($sticker);
			}
	
			function updateStickerVisuals($el, p) {
				$el.data('props', p);
				$el.css({
					'width': p.width,
					'top': p.top, 
					'left': p.left,
					'transform': `translate(-50%, -50%) rotate(${p.rotation}deg) scale(${p.scale}) scaleX(${p.flip})`
				});
			}
	
			// 3. SELECCIÓ
			$(document).on('mousedown', '.sr-sticker-item', function(e){
				e.stopPropagation(); 
				if(e.button !== 0) return; 
	
				let $wrapper = $(this).closest('.sr-slide-wrapper');
				// Si no estem en mode stickers, activem el mode (sense obrir modal)
				if(!$wrapper.hasClass('mode-stickers')) {
					$wrapper.find('.sr-mode-btn[data-mode="stickers"]').trigger('click', [true]); // true = preventModal
				}
	
				selectSticker($(this));
			});
	
			$(document).on('mousedown', function(e){
				if(!$(e.target).closest('.sr-sticker-item, .sr-sticker-side-menu').length) {
					deselectAllStickers();
				}
			});
	
			function selectSticker($sticker) {
				deselectAllStickers(); 
				activeSticker = $sticker;
				$sticker.addClass('is-active');
				const $wrapper = $sticker.closest('.sr-slide-wrapper');
				renderSideMenu($wrapper, $sticker);
			}
	
			function deselectAllStickers() {
				$('.sr-sticker-item').removeClass('is-active');
				$('.sr-sticker-side-menu').remove();
				activeSticker = null;
			}
	
			function renderSideMenu($wrapper, $sticker) {
				$('.sr-sticker-side-menu').remove(); 
				const p = $sticker.data('props');
				
				// Obtenim el tipus de diapositiva
				const slideType = $wrapper.find('.sr-slide').data('slide-type') || 'general';
				const labelType = slideType.charAt(0).toUpperCase() + slideType.slice(1);
	
				const html = `
				<div class="sr-sticker-side-menu">
					<h4>Editar Sticker</h4>
					
					<div class="sticker-ctrl-row">
						<label>Mida</label>
						<input type="range" class="stk-size" min="50" max="600" value="${parseInt(p.width)}" style="flex:1">
					</div>
					<div class="sticker-ctrl-row">
						<label>Rotació</label>
						<input type="range" class="stk-rot" min="-180" max="180" value="${p.rotation}" style="flex:1">
					</div>
					<div class="sticker-ctrl-row">
						<label>Capa</label>
						<button class="button button-small stk-layer-btn" data-val="top" ${p.layer==='top'?'disabled':''}>Davant</button>
						<button class="button button-small stk-layer-btn" data-val="bottom" ${p.layer==='bottom'?'disabled':''}>Darrere</button>
					</div>
					<div class="sticker-ctrl-row">
						<label>Mirall</label>
						<button class="button button-small stk-flip" data-val="${p.flip*-1}">Voltejar H</button>
					</div>
					
					<hr style="margin: 10px 0; border:0; border-top:1px solid #eee;">
					
					<div class="sticker-ctrl-row">
						<button class="button button-primary stk-save-db" style="width:100%; justify-content:center;">💾 Guardar a "${labelType}"</button>
					</div>
					<p style="font-size:10px; color:#666; margin:0; line-height:1.2;">Guarda aquesta disposició de stickers per a totes les pàgines "${labelType}" (futurs usuaris inclosos).</p>
	
					<div class="sticker-ctrl-row" style="margin-top:10px;">
						<button class="button button-link-delete stk-delete" style="color:#a00; text-decoration:none;">Eliminar i Guardar</button>
					</div>
				</div>`;
				
				$wrapper.append(html);
			}
	
			// 4. EVENTS CONTROLS STICKER
			$(document).on('input', '.stk-size', function(){
				if(!activeSticker) return;
				let p = activeSticker.data('props');
				p.width = $(this).val() + 'px';
				updateStickerVisuals(activeSticker, p);
			});
	
			$(document).on('input', '.stk-rot', function(){
				if(!activeSticker) return;
				let p = activeSticker.data('props');
				p.rotation = $(this).val();
				updateStickerVisuals(activeSticker, p);
			});
	
			$(document).on('click', '.stk-flip', function(e){
				e.preventDefault();
				if(!activeSticker) return;
				let p = activeSticker.data('props');
				p.flip = $(this).data('val');
				$(this).data('val', p.flip * -1);
				updateStickerVisuals(activeSticker, p);
			});
	
			$(document).on('click', '.stk-layer-btn', function(e){
				e.preventDefault();
				if(!activeSticker) return;
				let newVal = $(this).data('val');
				let p = activeSticker.data('props');
				if(p.layer !== newVal) {
					p.layer = newVal;
					let $slide = activeSticker.closest('.sr-slide');
					let targetClass = (newVal === 'top') ? '.sr-stickers-layer-top' : '.sr-stickers-layer-bottom';
					activeSticker.detach().appendTo($slide.find(targetClass));
					updateStickerVisuals(activeSticker, p);
					selectSticker(activeSticker);
				}
			});
	
			// --- ELIMINAR I GUARDAR AUTOMÀTICAMENT ---
			$(document).on('click', '.stk-delete', function(e){
				e.preventDefault();
				if(activeSticker) {
					if(!confirm("Segur que vols eliminar aquest sticker? Es guardarà automàticament que l'has esborrat.")) return;
	
					const $slide = activeSticker.closest('.sr-slide');
					const slideType = $slide.data('slide-type');
	
					activeSticker.remove();
					deselectAllStickers();
	
					// GUARDAT AUTOMÀTIC (SILENCIÓS O AMB TOAST)
					sr_perform_save_stickers($slide, slideType, false); // false = mostra alert "Guardat correctament"
				}
			});
	
			// --- GUARDAR MANUALMENT ---
			$(document).on('click', '.stk-save-db', function(e){
				e.preventDefault();
				if(!activeSticker) return;
				
				const $slide = activeSticker.closest('.sr-slide');
				const slideType = $slide.data('slide-type');
				const labelType = slideType.charAt(0).toUpperCase() + slideType.slice(1);
	
				if(!confirm(`Vols guardar la disposició actual de stickers per a "${labelType}"?`)) return;
	
				sr_perform_save_stickers($slide, slideType, false);
			});
	
			// 5. DRAG & DROP
			let isDragSticker = false;
			let dragStartX, dragStartY, dragStartLeft, dragStartTop;
	
			$(document).on('mousedown', '.sr-sticker-item', function(e){
				if(e.button !== 0) return;
				isDragSticker = true;
				activeSticker = $(this);
				dragStartX = e.clientX;
				dragStartY = e.clientY;
				let cssLeft = parseFloat(activeSticker.css('left')) || 0;
				let cssTop = parseFloat(activeSticker.css('top')) || 0;
				dragStartLeft = cssLeft;
				dragStartTop = cssTop;
				activeSticker.css('cursor', 'grabbing');
			});
	
			$(document).on('mousemove', function(e){
				if(!isDragSticker || !activeSticker) return;
				e.preventDefault();
				let dx = e.clientX - dragStartX;
				let dy = e.clientY - dragStartY;
				let newLeft = dragStartLeft + dx;
				let newTop = dragStartTop + dy;
				activeSticker.css({ left: newLeft + 'px', top: newTop + 'px' });
				let p = activeSticker.data('props');
				p.left = newLeft + 'px';
				p.top = newTop + 'px';
			});
	
			$(document).on('mouseup', function(){
				if(isDragSticker && activeSticker) {
					activeSticker.css('cursor', 'grab');
				}
				isDragSticker = false;
			});
	
			// ==========================================
			//  UI & EVENTS PRINCIPALS (MODES)
			// ==========================================
	
			$(document).on('click', '.sr-mode-btn', function(e, preventModal){
				e.preventDefault();
				let btn = $(this);
				let mode = btn.data('mode'); 
				let wrapper = btn.closest('.sr-slide-wrapper');
				let actions = btn.closest('.sr-actions');
				let slideId = '#' + wrapper.find('.sr-slide').attr('id');
	
				// Check estat previ
				let wasInStickerMode = wrapper.hasClass('mode-stickers');
				let stickerCount = wrapper.find('.sr-sticker-item').length;
	
				btn.parent().find('.sr-mode-btn').removeClass('active');
				btn.addClass('active');
				wrapper.removeClass('mode-text mode-image mode-stickers').addClass('mode-' + mode);
	
				if(mode === 'image') { 
					actions.find('.sr-zoom-control').addClass('active'); 
					deselectAllStickers();
				} 
				else if(mode === 'stickers') {
					actions.find('.sr-zoom-control').removeClass('active');
					// LÒGICA CONTEXTUAL: 
					// Obre modal si no n'hi ha cap O si ja estavem en aquest mode (click repetit per afegir)
					if(preventModal !== true) {
						if (stickerCount === 0 || wasInStickerMode) {
							openStickerPicker(slideId);
						}
					}
				}
				else { // text
					actions.find('.sr-zoom-control').removeClass('active'); 
					deselectAllStickers();
				}
			});
	
			// ... (Funcions UI comuns) ...
			function sr_actualitzar_valors_ui() {
				$('.sr-tune-val[data-key="contrast"]').text(SR_PORTADA_AJUSTOS.contrast.toFixed(2));
				$('.sr-tune-val[data-key="gamma"]').text(SR_PORTADA_AJUSTOS.gamma.toFixed(2));
				$('.sr-tune-val[data-key="brightness"]').text(parseInt(SR_PORTADA_AJUSTOS.brightness, 10));
				$('.sr-tune-val[data-key="blackBoost"]').text(parseInt(SR_PORTADA_AJUSTOS.blackBoost, 10));
				$('.sr-tune-val[data-key="grocMinim"]').text(SR_PORTADA_AJUSTOS.grocMinim.toFixed(2));
				$('.sr-tune-val[data-key="grocPunch"]').text(SR_PORTADA_AJUSTOS.grocPunch.toFixed(2));
				
				$('.sr-tune[data-key="contrast"]').val(SR_PORTADA_AJUSTOS.contrast);
				$('.sr-tune[data-key="gamma"]').val(SR_PORTADA_AJUSTOS.gamma);
				$('.sr-tune[data-key="brightness"]').val(SR_PORTADA_AJUSTOS.brightness);
				$('.sr-tune[data-key="blackBoost"]').val(SR_PORTADA_AJUSTOS.blackBoost);
				$('.sr-tune[data-key="grocMinim"]').val(SR_PORTADA_AJUSTOS.grocMinim);
				$('.sr-tune[data-key="grocPunch"]').val(SR_PORTADA_AJUSTOS.grocPunch);
			}
	
			async function sr_recalcular_preview_portada() {
				const $slide0 = $('#slide-0');
				if ($slide0.length === 0) return;
				const original = $slide0.attr('data-cover-original') || '';
				const isHoraris = $slide0.hasClass('style-horaris');
				let novaFinal;
				if (!isHoraris) {
					const nouBn = await sr_generar_byn_contrast_dataurl(original, SR_PORTADA_AJUSTOS);
					SR_PORTADA_LAST_BN = nouBn; 
					novaFinal = await sr_generar_groc_negre_dataurl(nouBn, Object.assign({}, SR_PORTADA_AJUSTOS, { ja_processada: true }));
				} else {
					novaFinal = await sr_generar_byn_contrast_dataurl(original, { ...SR_PORTADA_AJUSTOS, bn: false });
					SR_PORTADA_LAST_BN = novaFinal;
				}
				const $bgImg = $slide0.find('.sr-bg-img').first();
				if ($bgImg.length) {
					$bgImg[0].style.backgroundImage = `url("${novaFinal}")`;
				}
				$slide0.find('.sr-bg-container').css('mix-blend-mode', 'normal').css('opacity', '1');
			}
	
			function getSmartFilename(slideId) {
				let slide = $(slideId);
				let filename = "";
				let totalSlides = $('.sr-slide').length - 1; 
				
				if(slideId === '#slide-0') {
					let type = slide.find('.sr-title-block').first().text().trim();
					let date = slide.find('.sr-date-block').text().trim();
					filename = "00_PORTADA_" + type + "_" + date;
				} else {
					let type = slide.find('.sr-ph-title').text().trim().replace(' JORNADA', '');
					let date = slide.find('.sr-ph-date').text().trim();
					let pageText = slide.find('.sr-pagination-num b').text().trim();
					let current = parseInt(pageText);
					filename = pad(current) + "-" + pad(totalSlides) + "_" + type + "_" + date;
				}
				filename = filename.replace(/\s+/g, '_').replace(/[^a-zA-Z0-9_\-]/g, '');
				return filename + ".png";
			}
	
			$(document).on('input', '.sr-tune', function() {
				const clau = $(this).data('key');
				let val = $(this).val();
				if (clau === 'brightness' || clau === 'blackBoost') val = parseInt(val, 10);
				else val = parseFloat(val);
				SR_PORTADA_AJUSTOS[clau] = val;
				$(this).siblings('.sr-tune-val').text(val.toFixed(clau === 'brightness' || clau === 'blackBoost' ? 0 : 2));
				if (sr_tune_timer) clearTimeout(sr_tune_timer);
				sr_tune_timer = setTimeout(() => { sr_recalcular_preview_portada(); }, 120);
			});
			$(document).on('click', '.sr-tune-reset', function() {
				SR_PORTADA_AJUSTOS = Object.assign({}, SR_PORTADA_AJUSTOS_DEFAULT);
				sr_actualitzar_valors_ui();
				sr_recalcular_preview_portada();
			});
	
			$('#btnFmtPortrait').on('click', function(e) {
				e.preventDefault();
				$(this).addClass('active').siblings().removeClass('active');
				$(this).addClass('button-primary'); $('#btnFmtStory').removeClass('button-primary');
				$('.sr-slide').removeClass('format-story');
			});
			$('#btnFmtStory').on('click', function(e) {
				e.preventDefault();
				$(this).addClass('active').siblings().removeClass('active');
				$(this).addClass('button-primary'); $('#btnFmtPortrait').removeClass('button-primary');
				$('.sr-slide').addClass('format-story');
			});
	
			let mediaUploader;
			$('#btnUploadCover').on('click', function(e) {
				e.preventDefault();
				if (mediaUploader) { mediaUploader.open(); return; }
				mediaUploader = wp.media.frames.file_frame = wp.media({ title: 'Tria Foto Portada Per Defecte', button: { text: 'Fer servir' }, multiple: false });
				mediaUploader.on('select', function() {
					try {
						const attachment = mediaUploader.state().get('selection').first().toJSON();
						if (attachment && attachment.url) $('#coverImageUrl').val(attachment.url);
					} catch (err) {}
				});
				mediaUploader.open();
			});
	
			$('#btnLoadData').on('click', async function() {
				const start = $('#dateStart').val();
				const end = $('#dateEnd').val();
				const realCoverImg = $('#coverImageUrl').val() || 'https://via.placeholder.com/800x1000/000000/FFFFFF?text=Fons+Base';
				const ghostImg = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
			
				if(!start || !end) { alert('Selecciona dates!'); return; }
			
				$('#preview-area').css('opacity', '0'); 
				$('#preview-area').empty();
				$('#btnDownloadAll').hide();
				$('#zip-loading').hide();
				$('#loading').show().html('<span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span> Iniciant motor de generació...');
			
				$.ajax({
					url: API_URL, method: 'GET', data: { inici: start, fi: end, limit: 100 },
					success: async function(response) {
						if(response.length === 0) { $('#loading').hide(); $('#preview-area').css('opacity', '1'); alert("No s'han trobat partits."); return; }
						try {
							try { await document.fonts.load("1em Montserrat"); await document.fonts.ready; } catch(e) {}
			
							$('#loading').html('<span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span> Fase 1/3: Escalfant motor...');
							await generateSlides(response, ghostImg, start, end);
							await new Promise(resolve => setTimeout(resolve, 400));
			
							$('#loading').html('<span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span> Fase 2/3: Calibrant espais...');
							$('#preview-area').empty(); $('#sr-slide-mesurador').remove();
							await generateSlides(response, ghostImg, start, end);
							await new Promise(resolve => setTimeout(resolve, 600));
	
							$('#loading').html('<span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span> Fase 3/3: Generació final...');
							$('#preview-area').empty(); $('#sr-slide-mesurador').remove();
							await generateSlides(response, ghostImg, start, end);
			
							$('#loading').html('<span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span> Aplicant disseny gràfic...');
	
							const isHorarisMode = $('#slide-0').hasClass('style-horaris');
							
							// 1. Assignem la imatge base
							$('#slide-0').attr('data-cover-original', realCoverImg);
							$('.sr-slide').not('#slide-0').attr('data-cover-original', realCoverImg);
							$('.sr-slide').find('.sr-bg-img').css('background-image', `url('${realCoverImg}')`);
							
							if(isHorarisMode) {
								// Mode Horaris (fosc)
								$('.sr-slide').not('#slide-0').find('.sr-bg-img').css('display', 'none');
								SR_PORTADA_AJUSTOS.brightness = -60; SR_PORTADA_AJUSTOS.contrast = 1.1; SR_PORTADA_AJUSTOS.saturation = 0.4;
								sr_generar_byn_contrast_dataurl(realCoverImg, { ...SR_PORTADA_AJUSTOS, bn: false }).then(url => {
									$('#slide-0').find('.sr-bg-img').css('background-image', `url('${url}')`).css('filter', 'none');
								});
							} else {
								// Mode Portada Groga: SIMULACIÓ DE MOVIMENT HUMÀ
								// 1. Netegem memòria cau per obligar a re-processar
								SR_PORTADA_LAST_BN = null;
							
								// 2. Esperem 500ms a que la imatge estigui al navegador
								setTimeout(function(){
									// 3. Agafem el control de contrast
									let $contrast = $('.sr-tune[data-key="contrast"]');
									let valOriginal = parseFloat($contrast.val());
									
									// 4. MOVIMENT 1: El movem una mica (dispara el processat)
									$contrast.val(valOriginal + 0.2).trigger('input');
									
									// 5. MOVIMENT 2: Esperem 300ms (important!) i el tornem a lloc
									// Aquest "300ms" és clau perquè dona temps al primer moviment a acabar
									setTimeout(function(){
										$contrast.val(valOriginal).trigger('input');
									}, 300);
								}, 500);
							}
							
							sr_actualitzar_valors_ui();
							await new Promise(resolve => setTimeout(resolve, 200));
							$('#preview-area').animate({ opacity: 1 }, 500); 
							$('#loading').hide().html('<span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span> Carregant dades de l\'API...');
							$('#btnDownloadAll').show();
						} catch(e) { $('#loading').hide(); $('#preview-area').css('opacity', '1'); alert("Hi ha hagut un error inesperat: " + e.message); }
					},
					error: function() { $('#loading').hide(); $('#preview-area').css('opacity', '1'); alert("Error connectant amb l'API."); }
				});
			});
				
			$(document).on('change', '.chk-select-slide', function() {
				let chk = $(this);
				let wrapper = chk.closest('.sr-slide-wrapper');
				let label = chk.parent();
				if(chk.is(':checked')) { wrapper.addClass('is-selected'); label.addClass('active-check'); } 
				else { wrapper.removeClass('is-selected'); label.removeClass('active-check'); }
			});
	
			// BACKGROUND DRAG (FONS)
			let isDragging = false;
			let startX, startY, initialTranslateX, initialTranslateY, activeImg = null;
	
			$(document).on('mousedown', '.sr-slide-wrapper.mode-image .sr-slide', function(e) {
				if(e.target.closest('.sr-sticker-item')) return; 
				e.preventDefault();
				isDragging = true;
				activeImg = $(this).find('.sr-bg-img'); 
				const transform = activeImg.css('transform');
				let matrix = transform.match(/^matrix\((.+)\)$/);
				if (matrix) {
					const values = matrix[1].split(',');
					initialTranslateX = parseFloat(values[4]);
					initialTranslateY = parseFloat(values[5]);
				} else { initialTranslateX = 0; initialTranslateY = 0; }
				startX = e.clientX; startY = e.clientY;
				activeImg.css('cursor', 'grabbing');
			});
	
			$(document).on('mousemove', function(e) {
				if (!isDragging || !activeImg) return;
				e.preventDefault();
				const dx = e.clientX - startX; const dy = e.clientY - startY;
				const currentScale = activeImg.attr('data-scale') || 1;
				const newX = initialTranslateX + dx; const newY = initialTranslateY + dy;
				activeImg.css('transform', `translate(${newX}px, ${newY}px) scale(${currentScale})`);
			});
	
			$(document).on('mouseup mouseleave', function() {
				if(isDragging && activeImg) { activeImg.css('cursor', 'grab'); }
				isDragging = false; activeImg = null;
			});
	
			$(document).on('input', '.zoom-range', function() {
				let scaleVal = $(this).val();
				let targetId = $(this).data('target');
				let img = $(targetId).find('.sr-bg-img'); 
				const transform = img.css('transform');
				let tx = 0, ty = 0;
				let matrix = transform.match(/^matrix\((.+)\)$/);
				if (matrix) { const values = matrix[1].split(','); tx = parseFloat(values[4]); ty = parseFloat(values[5]); }
				img.attr('data-scale', scaleVal);
				img.css('transform', `translate(${tx}px, ${ty}px) scale(${scaleVal})`);
			});
	
			let singleUploader;
			$(document).on('click', '.btn-change-bg', function(e) {
				e.preventDefault();
				let btn = $(this); let targetId = btn.data('target'); let targetSlide = $(targetId); let targetImg = targetSlide.find('.sr-bg-img'); 
				if (singleUploader) { singleUploader.open(); }
				else { singleUploader = wp.media.frames.file_frame = wp.media({ title: 'Canviar Fons Diapositiva', button: { text: 'Aplicar' }, multiple: false }); }
				singleUploader.off('select');
				singleUploader.on('select', async function() {
					const attachment = singleUploader.state().get('selection').first().toJSON();
					const novaUrl = attachment.url;
						const isHoraris = targetSlide.hasClass('style-horaris');
						
						// A. Assignem la imatge al DOM (visualment)
						if (targetId === '#slide-0') {
							const isHoraris = targetSlide.hasClass('style-horaris');
							
							// 1. Guardem l'original
							targetSlide.attr('data-cover-original', novaUrl);
						
							// 2. Feedback visual (pensant...)
							targetImg.css('opacity', '0.3');
							SR_PORTADA_LAST_BN = null;
						
							if (!isHoraris) {
								// === MODE RESULTATS (GROC) ===
								// Fem servir SR_PORTADA_AJUSTOS tal qual està configurat globalment.
								sr_generar_byn_contrast_dataurl(novaUrl, SR_PORTADA_AJUSTOS)
									.then(function(imgBn){
										SR_PORTADA_LAST_BN = imgBn;
										// Apliquem el groc sobre el BN resultant
										return sr_generar_groc_negre_dataurl(imgBn, Object.assign({}, SR_PORTADA_AJUSTOS, { ja_processada: true }));
									})
									.then(function(imgFinal){
										targetImg.css('background-image', 'url(' + imgFinal + ')');
										targetImg.css('opacity', '1');
									});
						
							} else {
								// === MODE HORARIS (FOSC) ===
								// També fem servir SR_PORTADA_AJUSTOS globals.
								// L'única cosa que hem de forçar és que NO ho passi a Blanc i Negre pur (bn: false),
								// perquè el mode Horaris manté una mica de color (desaturat), tal com està definit a la configuració global.
								
								const ajustosReals = Object.assign({}, SR_PORTADA_AJUSTOS, { bn: false });
						
								sr_generar_byn_contrast_dataurl(novaUrl, ajustosReals)
									.then(function(imgFinal){
										targetImg.css('background-image', 'url(' + imgFinal + ')');
										targetImg.css('filter', 'none'); 
										targetImg.css('opacity', '1');
									});
							}
						} else {
							// Slides Normals
							targetImg.css('background-image', 'url(' + novaUrl + ')');
							targetSlide.attr('data-cover-original', novaUrl);
							targetImg.css('display', 'block');
						}
					
						// B. CÀLCUL MATEMÀTIC INFAL·LIBLE (El "Replantejament")
						// No esperem al navegador. Carreguem la imatge en memòria i calculem els píxels nosaltres.
						let imgTemp = new Image();
						imgTemp.onload = function() {
							// 1. Dimensions del contenidor (sabem segur quant fan)
							let isStory = targetSlide.hasClass('format-story');
							let cw = 540; 
							let ch = isStory ? 960 : 675;
					
							// 2. Dimensions de la teva imatge
							let iw = this.naturalWidth || 100;
							let ih = this.naturalHeight || 100;
					
							// 3. Matemàtica "Cover" (omplir sense deformar)
							let scale = Math.max(cw / iw, ch / ih);
							let finalW = Math.ceil(iw * scale);
							let finalH = Math.ceil(ih * scale);
					
							// 4. APLICAR ELS PÍXELS DIRECTAMENT
							// Això simula l'efecte de moure el zoom però ja posat al lloc correcte.
							targetImg.css({
								'background-size': finalW + 'px ' + finalH + 'px',
								'background-position': 'center center',
								'transform': 'translate(0px, 0px) scale(1)',
								'transition': 'none' // Evitem animacions que facin "salts"
							});
							targetImg.attr('data-scale', 1);
					
							// 5. Reset de la UI del zoom
							btn.closest('.sr-actions').find('.zoom-range').val(1);
							
							// 6. Evitem conflictes futurs
							targetImg.data('last-bg', novaUrl);
							targetImg.data('sizing-lock', false);
						};
						// Disparem la càrrega
						imgTemp.src = novaUrl;
					});
					singleUploader.open();
			});
	
			$(document).on('click', '.btn-apply-all', function(e) {
				e.preventDefault();
				let btn = $(this); let sourceSlideId = btn.data('target'); let sourceImg = $(sourceSlideId).find('.sr-bg-img'); let sourceSlider = btn.closest('.sr-actions').find('.zoom-range');
				let src = sourceImg.css('background-image'); let transform = sourceImg.css('transform'); let scaleData = sourceImg.attr('data-scale'); let sliderVal = sourceSlider.val();
				let selectedCheckboxes = $('.chk-select-slide:checked').not(`[value="${sourceSlideId}"]`);
				let targetIds = [];
				if (selectedCheckboxes.length > 0) { selectedCheckboxes.each(function() { targetIds.push($(this).val()); }); if(!confirm(`⚠️ Aplicar imatge a ${targetIds.length} seleccionades?`)) return; } 
				else { if(!confirm("ℹ️ Vols aplicar aquesta configuració a TOTES les altres?")) return; $('.sr-slide').each(function(){ let id = '#' + $(this).attr('id'); if(id !== sourceSlideId) targetIds.push(id); }); }
				targetIds.forEach(function(tid){
					let slide = $(tid); let img = slide.find('.sr-bg-img');
					img.css('background-image', src); img.css('transform', transform); img.attr('data-scale', scaleData); img.css('display', 'block'); 
					$(`.zoom-range[data-target="${tid}"]`).val(sliderVal);
				});
			});
	
			// EXPORTACIÓ
			$(document).on('click', '.download-btn', async function(e) {
				e.preventDefault();
				deselectAllStickers(); 
				
				let targetId = $(this).data('target');
				let element = document.getElementById(targetId);
				
				let $wrapper = $(element).closest('.sr-slide-wrapper');
				let originalMode = '';
				if($wrapper.hasClass('mode-stickers')) originalMode = 'mode-stickers';
				else if($wrapper.hasClass('mode-image')) originalMode = 'mode-image';
				else originalMode = 'mode-text';
				
				$wrapper.removeClass('mode-stickers mode-image').addClass('mode-text');
	
				let btn = $(this);
				let finalName = getSmartFilename('#' + targetId);
				btn.text('Generant...').prop('disabled', true);
				let restaurarPortada = null;
				let $bgImgElement = $(element).find('.sr-bg-img');
				let originalWillChange = $bgImgElement.css('will-change');
				$bgImgElement.css('will-change', 'auto');
				
				const originalWrapperTransform = $wrapper[0].style.transform || '';
				$wrapper[0].style.transform = 'none';
				const isHoraris = $(element).hasClass('style-horaris');
	
				if (targetId === 'slide-0') {
					try {
						const $slide0 = $('#slide-0'); const $bgContainer = $slide0.find('.sr-bg-container').first(); const $bgImg = $slide0.find('.sr-bg-img').first();
						const estat = { mixBlend: $bgContainer[0].style.mixBlendMode || '', opacity: $bgContainer[0].style.opacity || '', bg: $bgImg[0].style.backgroundImage || '' };
						let originalUrl = $slide0.attr('data-cover-original');
						if(!originalUrl) { let m = estat.bg.match(/url\((['"]?)(.*?)\1\)/i); if(m && m[2]) originalUrl = m[2]; }
						let finalDataUrl;
						if (!isHoraris) {
							let bn = await sr_generar_byn_contrast_dataurl(originalUrl, SR_PORTADA_AJUSTOS);
							finalDataUrl = await sr_generar_groc_negre_dataurl(bn, { ...SR_PORTADA_AJUSTOS, ja_processada: true });
						} else { finalDataUrl = await sr_generar_byn_contrast_dataurl(originalUrl, { ...SR_PORTADA_AJUSTOS, bn: false }); }
						$bgContainer[0].style.mixBlendMode = 'normal'; $bgContainer[0].style.opacity = '1'; $bgImg[0].style.backgroundImage = `url("${finalDataUrl}")`;
						restaurarPortada = function() { $bgContainer[0].style.mixBlendMode = estat.mixBlend; $bgContainer[0].style.opacity = estat.opacity; $bgImg[0].style.backgroundImage = estat.bg; };
					} catch (e2) { restaurarPortada = null; }
				}
				const restaurarSwapFons = sr_export_swap_bg_a_img(element);
				await new Promise(resolve => setTimeout(resolve, 60));
				try {
					let canvas = await html2canvas(element, { scale: 2, width: element.offsetWidth, height: element.offsetHeight, useCORS: true, allowTaint: true, backgroundColor: null });
					let link = document.createElement('a'); link.download = finalName; link.href = canvas.toDataURL("image/png"); link.click();
					btn.text('Descarregar').prop('disabled', false);
				} catch(err) { alert('Error generant.'); btn.text('Error').prop('disabled', false); } 
				finally {
					if (typeof restaurarSwapFons === 'function') restaurarSwapFons();
					if (typeof restaurarPortada === 'function') restaurarPortada();
					$bgImgElement.css('will-change', originalWillChange);
					$wrapper[0].style.transform = originalWrapperTransform;
					$wrapper.removeClass('mode-text').addClass(originalMode);
				}
			});
	
			$('#btnDownloadAll').on('click', async function(e) {
				e.preventDefault();
				deselectAllStickers(); 
				if(!confirm("Generar ZIP?")) return;
				let zip = new JSZip(); let slides = $('#preview-area .sr-slide');
				$('#zip-loading').show().css('display','flex');
				for (let i = 0; i < slides.length; i++) {
					let slide = slides[i]; let slideId = '#' + slide.id; let filename = getSmartFilename(slideId);
					$('#zip-loading').html(` <span class="dashicons dashicons-hourglass" style="font-size:20px;"></span> <strong>Processant:</strong> Imatge ${i + 1} de ${slides.length} (${filename})... `);
					await new Promise(resolve => setTimeout(resolve, 150));
					let restaurarPortada = null;
					const isHoraris = $(slide).hasClass('style-horaris');
					const $wrapper = $(slide).closest('.sr-slide-wrapper');
					
					let originalMode = '';
					if($wrapper.hasClass('mode-stickers')) originalMode = 'mode-stickers';
					else if($wrapper.hasClass('mode-image')) originalMode = 'mode-image';
					else originalMode = 'mode-text';
					
					$wrapper.removeClass('mode-stickers mode-image').addClass('mode-text');
	
					const originalWrapperTransform = $wrapper[0] ? ($wrapper[0].style.transform || '') : '';
					if ($wrapper[0]) $wrapper[0].style.transform = 'none';
	
					if (slide.id === 'slide-0') {
						try {
							const $slide0 = $('#slide-0'); const $bgContainer = $slide0.find('.sr-bg-container').first(); const $bgImg = $slide0.find('.sr-bg-img').first();
							const estat = { mixBlend: $bgContainer[0].style.mixBlendMode || '', opacity: $bgContainer[0].style.opacity || '', bg: $bgImg[0].style.backgroundImage || '' };
							let originalUrl = $slide0.attr('data-cover-original');
							if(!originalUrl) { let m = estat.bg.match(/url\((['"]?)(.*?)\1\)/i); if(m && m[2]) originalUrl = m[2]; }
							let finalDataUrl;
							if(!isHoraris) {
								let bn = await sr_generar_byn_contrast_dataurl(originalUrl, SR_PORTADA_AJUSTOS);
								finalDataUrl = await sr_generar_groc_negre_dataurl(bn, { ...SR_PORTADA_AJUSTOS, ja_processada: true });
							} else { finalDataUrl = await sr_generar_byn_contrast_dataurl(originalUrl, { ...SR_PORTADA_AJUSTOS, bn: false }); }
							$bgContainer[0].style.mixBlendMode = 'normal'; $bgContainer[0].style.opacity = '1'; $bgImg[0].style.backgroundImage = `url("${finalDataUrl}")`;
							restaurarPortada = function() { $bgContainer[0].style.mixBlendMode = estat.mixBlend; $bgContainer[0].style.opacity = estat.opacity; $bgImg[0].style.backgroundImage = estat.bg; };
						} catch(e2) { restaurarPortada = null; }
					}
					const restaurarSwapFons = sr_export_swap_bg_a_img(slide);
					await new Promise(resolve => setTimeout(resolve, 60));
					try {
						let canvas = await html2canvas(slide, { scale: 2, width: slide.offsetWidth, height: slide.offsetHeight, useCORS: true, allowTaint: true, backgroundColor: null });
						let blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
						zip.file(filename, blob);
					} catch(err) {} finally {
						if (typeof restaurarSwapFons === 'function') restaurarSwapFons();
						if (typeof restaurarPortada === 'function') restaurarPortada();
						if ($wrapper[0]) $wrapper[0].style.transform = originalWrapperTransform;
						$wrapper.removeClass('mode-text').addClass(originalMode);
					}
				}
				$('#zip-loading').html(` <span class="dashicons dashicons-download" style="font-size:20px;"></span> <strong>Comprimint:</strong> Generant arxiu ZIP final... `);
				let content = await zip.generateAsync({type:"blob"});
				saveAs(content, "Jornada_Completa.zip");
				$('#zip-loading').hide();
			});
	
			function isEscola(match) {
				let catName = (match.info && match.info.categoria_txt) ? match.info.categoria_txt.trim() : "";
				if(catName && CONFIG.escola_cats.includes(catName)) return true;
				if(match.taxonomies && match.taxonomies.categories) {
					for(let i=0; i < match.taxonomies.categories.length; i++){
						if(CONFIG.escola_cats.includes(match.taxonomies.categories[i])) return true;
					}
				}
				return false;
			}
	
			function createLinearSlides(matchList, type) {
				let matchesByDay = {};
				matchList.forEach(function(m) { let day = m.calendari.data_iso.split('T')[0]; if (!matchesByDay[day]) matchesByDay[day] = []; matchesByDay[day].push(m); });
				let sortedDays = Object.keys(matchesByDay).sort();
				let slides = [];
				const MAX_SLOTS = 6; 
				function getNiceDate(dateStr) {
					let d = new Date(dateStr); let weekday = d.toLocaleDateString('ca-ES', { weekday: 'long' }); weekday = weekday.charAt(0).toUpperCase() + weekday.slice(1);
					let dayNum = d.getDate(); let month = d.toLocaleDateString('ca-ES', { month: 'long' }); month = month.charAt(0).toUpperCase() + month.slice(1);
					return `${weekday}, ${dayNum} de ${month}`;
				}
				if (type === 'normal') {
					sortedDays.forEach(function(day) {
						let dayMatches = matchesByDay[day]; 
						for (let i = 0; i < dayMatches.length; i += MAX_SLOTS) {
							let group = dayMatches.slice(i, i + MAX_SLOTS); let dayHeader = getNiceDate(day);
							slides.push({ dateText: dayHeader, matches: group, type: type });
						}
					});
				} else {
					let orderedList = [];
					sortedDays.forEach(function(day) { orderedList = orderedList.concat(matchesByDay[day]); });
					let currentPageItems = []; let lastDayInPage = null;
					orderedList.forEach(function(match) {
						let matchDay = match.calendari.data_iso.split('T')[0]; let cost = 1; let needsSeparator = false;
						if (currentPageItems.length > 0 && matchDay !== lastDayInPage) { needsSeparator = true; cost = 2; }
						if (currentPageItems.length + cost <= MAX_SLOTS) {
							if (needsSeparator) { currentPageItems.push({ is_separator: true, text: getNiceDate(matchDay), date_iso: matchDay }); }
							currentPageItems.push(match); lastDayInPage = matchDay;
						} else {
							if (currentPageItems.length > 0) { slides.push(createEscolaSlideObject(currentPageItems, type)); }
							currentPageItems = [match]; lastDayInPage = matchDay;
						}
					});
					if (currentPageItems.length > 0) { slides.push(createEscolaSlideObject(currentPageItems, type)); }
				}
				function createEscolaSlideObject(items, type) {
					let realMatches = items.filter(x => !x.is_separator); let headerText = "";
					if (realMatches.length > 0) { let firstDate = realMatches[0].calendari.data_iso.split('T')[0]; headerText = getNiceDate(firstDate); }
					return { dateText: headerText, matches: items, type: type };
				}
				return slides;
			}
	
			async function generateSlides(matches, coverImg, startStr, endStr) {
				const area = $('#preview-area');
				let activeMatches = matches.filter(m => {
					let esDescans = m.calendari.es_descans; let textDescans = m.calendari.text_descans || "";
					if (esDescans === true || esDescans === 'yes' || (typeof esDescans === 'string' && esDescans.trim() === 'Descans')) {
						if (textDescans && typeof textDescans === 'string' && textDescans.toUpperCase().includes('AJORN')) { return true; }
						return false; 
					}
					return true;
				});
				if (activeMatches.length === 0) { alert("Només descansos."); return; }
				let matchesNormal = []; let matchesEscola = [];
				activeMatches.forEach(m => { if(isEscola(m)) matchesEscola.push(m); else matchesNormal.push(m); });
				let hasAnyResult = false;
				activeMatches.forEach(m => { let rL = m.local.marcador; let rV = m.visitant.marcador; if( (rL !== "" && rL !== null) || (rV !== "" && rV !== null) ) hasAnyResult = true; });
				let mainTitleText = hasAnyResult ? "RESUM" : "HORARIS";
				let isHoraris = !hasAnyResult; 
				let currentIconSrc = hasAnyResult ? CONFIG.icon_results : CONFIG.icon_schedule;
				let iconHtml = currentIconSrc ? `<div class="icon-top-left"><img src="${currentIconSrc}" style="width:100%;height:100%;object-fit:contain;"></div>` : `<div class="icon-top-left" style="border:2px dashed #000; opacity:0.3; font-size:10px; text-align:center;">Icona</div>`;
				let textDates = ""; const d1 = new Date(startStr); const d2 = new Date(endStr);
				const monthNames = ["Gener", "Febrer", "Març", "Abril", "Maig", "Juny", "Juliol", "Agost", "Setembre", "Octubre", "Novembre", "Desembre"];
				const day1 = d1.getDate(); const day2 = d2.getDate(); const m1 = monthNames[d1.getMonth()]; const m2 = monthNames[d2.getMonth()];
				if (startStr === endStr) textDates = `${day1} de ${m1}`; else if (d1.getMonth() === d2.getMonth()) textDates = `${day1} i ${day2} de ${m1}`; else textDates = `${day1} de ${m1} i ${day2} de ${m2}`;
				const isStoryMode = $('#btnFmtStory').hasClass('button-primary');
				const formatClass = isStoryMode ? 'format-story' : '';
				let coverHtml;
				let textureHtml = isHoraris ? `<div class="sr-texture-overlay" style="background-image:url('${CONFIG.texture_url}')"></div>` : '';
				let extraClasses = isHoraris ? 'style-horaris' : '';
				
				// AFEGIT: data-slide-type="cover" i capes stickers. AFEGIT: BOTO MODE STICKERS
				coverHtml = `
				<div class="sr-slide-wrapper mode-text">
					<div class="sr-slide ${formatClass} ${extraClasses}" id="slide-0" data-cover-original="${coverImg}" data-slide-type="cover">
						${iconHtml}
						${textureHtml}
						<div class="sr-bg-container" style="mix-blend-mode: normal; opacity: 1;">
							<div class="sr-bg-img" style="background-image: url('${coverImg}'); transform: translate(0px, 0px) scale(1);" data-scale="1"></div>
						</div>
						
						<div class="sr-stickers-layer-bottom"></div>
	
						<div class="sr-content-layer">
							<div class="sr-cover-content">
								<div class="sr-title-box">
									<div class="sr-title-row"><div class="sr-title-block" contenteditable="true">${mainTitleText}</div></div>
									<div class="sr-title-row"><div class="sr-title-block" contenteditable="true">JORNADA</div></div>
									<div class="sr-date-row"><div class="sr-date-block" contenteditable="true">${textDates}</div></div>
								</div>
							</div>
							<div class="sr-arrow-cover dashicons dashicons-arrow-right-alt2"></div>
						</div>
	
						<div class="sr-stickers-layer-top"></div>
	
						<div class="sr-footer-container">
							<div class="sr-footer-slant"></div>
							<div class="sr-footer-logos">${FOOTER_HTML}</div>
						</div>
					</div>
					<div class="sr-actions" data-actions-for="slide-0">
						<div class="sr-actions-row">
							<div class="sr-mode-switcher">
								<button class="sr-mode-btn active" data-mode="text"><span class="dashicons dashicons-edit"></span> Text</button>
								<button class="sr-mode-btn" data-mode="stickers"><span class="dashicons dashicons-smiley"></span> Stickers</button>
								<button class="sr-mode-btn" data-mode="image"><span class="dashicons dashicons-format-image"></span> Fons</button>
							</div>
							<label class="sr-select-label"><input type="checkbox" class="chk-select-slide" value="#slide-0"> Seleccionar</label>
						</div>
						<div class="sr-actions-row">
							<button class="button btn-change-bg" data-target="#slide-0">Canviar Foto</button>
						</div>
						<div class="sr-actions-row">
							<div class="sr-zoom-control">
								<span class="dashicons dashicons-search"></span>
								<input type="range" class="zoom-range" min="0.1" max="5" step="0.1" value="1" data-target="#slide-0">
								<button class="button btn-apply-all" data-target="#slide-0" title="Aplicar a altres"><span class="dashicons dashicons-controls-repeat"></span></button>
							</div>
						</div>
						<div>
							<details class="sr-portada-tuner" style="margin-top:10px;">
								<summary style="cursor:pointer; font-weight:600;">Ajustos Portada</summary>
								<div style="padding:10px; display:flex; flex-direction:column; gap:10px;">
									<div style="display:flex; gap:10px; align-items:center;">
										<div style="width:110px; font-size:12px;">Contrast</div>
										<input class="sr-tune" data-key="contrast" type="range" min="0.5" max="2.5" step="0.05" value="${SR_PORTADA_AJUSTOS.contrast}" style="flex:1;">
										<div style="width:60px; text-align:right; font-family:monospace;" class="sr-tune-val" data-key="contrast">${SR_PORTADA_AJUSTOS.contrast.toFixed(2)}</div>
									</div>
									<div style="display:flex; gap:10px; align-items:center;">
										<div style="width:110px; font-size:12px;">Gamma</div>
										<input class="sr-tune" data-key="gamma" type="range" min="0.6" max="1.4" step="0.02" value="${SR_PORTADA_AJUSTOS.gamma}" style="flex:1;">
										<div style="width:60px; text-align:right; font-family:monospace;" class="sr-tune-val" data-key="gamma">${SR_PORTADA_AJUSTOS.gamma.toFixed(2)}</div>
									</div>
									<div style="display:flex; gap:10px; align-items:center;">
										<div style="width:110px; font-size:12px;">Brillo</div>
										<input class="sr-tune" data-key="brightness" type="range" min="-100" max="60" step="1" value="${SR_PORTADA_AJUSTOS.brightness}" style="flex:1;">
										<div style="width:60px; text-align:right; font-family:monospace;" class="sr-tune-val" data-key="brightness">${SR_PORTADA_AJUSTOS.brightness}</div>
									</div>
									<div style="display:flex; gap:10px; align-items:center;">
										<div style="width:110px; font-size:12px;">Negres</div>
										<input class="sr-tune" data-key="blackBoost" type="range" min="0" max="40" step="1" value="${SR_PORTADA_AJUSTOS.blackBoost}" style="flex:1;">
										<div style="width:60px; text-align:right; font-family:monospace;" class="sr-tune-val" data-key="blackBoost">${SR_PORTADA_AJUSTOS.blackBoost}</div>
									</div>
									<hr style="margin:5px 0;">
									<div style="display:flex; gap:10px; align-items:center;">
										<div style="width:110px; font-size:12px;">Groc mínim</div>
										<input class="sr-tune" data-key="grocMinim" type="range" min="0" max="0.35" step="0.01" value="${SR_PORTADA_AJUSTOS.grocMinim}" style="flex:1;">
										<div style="width:60px; text-align:right; font-family:monospace;" class="sr-tune-val" data-key="grocMinim">${SR_PORTADA_AJUSTOS.grocMinim.toFixed(2)}</div>
									</div>
									<div style="display:flex; gap:10px; align-items:center;">
										<div style="width:110px; font-size:12px;">Punch groc</div>
										<input class="sr-tune" data-key="grocPunch" type="range" min="0.6" max="2.0" step="0.05" value="${SR_PORTADA_AJUSTOS.grocPunch}" style="flex:1;">
										<div style="width:60px; text-align:right; font-family:monospace;" class="sr-tune-val" data-key="grocPunch">${SR_PORTADA_AJUSTOS.grocPunch.toFixed(2)}</div>
									</div>
									<div style="display:flex; gap:10px; justify-content:flex-end;">
										<button type="button" class="button sr-tune-reset">Restaurar valors</button>
									</div>
								</div>
							</details>
						</div>
						<button class="button button-primary download-btn" data-target="slide-0" style="width:100%">Descarregar Portada</button>
					</div>
				</div>`;
				area.append(coverHtml);
	
				// AUTO-LOAD STICKERS PORTADA
				if(SAVED_CONFIG['cover']) {
					 SAVED_CONFIG['cover'].forEach(s => addStickerToSlide($('#slide-0'), s.url, s));
				}
	
				function sr_render_matches_html(items) {
					let html = '';
					items.forEach(item => {
						// 1. Separadors (Dates)
						if (item && item.is_separator) {
							html += `<div class="sr-match-row separator" style="background:transparent; display:block; text-align:center; padding:5px 0; margin-top:15px; margin-bottom:5px; box-shadow:none; transform:none;"><div contenteditable="true" style="font-size:20px; font-weight:500; color:#000; display:inline-block; font-family:'Montserrat', sans-serif;">${item.text}</div></div>`;
							return;
						}
				
						// 2. Preparar Dades
						let match = item; 
						let categoryText = (match.info && match.info.categoria_txt) ? match.info.categoria_txt.trim() : '';
						
						// Equip Local
						let nomLocalFinal = match.local.nom; 
						let urlLocal = match.local.escut || ''; 
						let isLocalMataro = (urlLocal.indexOf('Mataro.png') !== -1 || urlLocal.indexOf('escut_cjhm_q.png') !== -1);
						if (isLocalMataro && categoryText !== '') { nomLocalFinal = categoryText; }
						
						// Equip Visitant
						let nomVisitantFinal = match.visitant.nom; 
						let urlVisit = match.visitant.escut || ''; 
						let isVisitMataro = (urlVisit.indexOf('Mataro.png') !== -1 || urlVisit.indexOf('escut_cjhm_q.png') !== -1);
						if (isVisitMataro && categoryText !== '') { nomVisitantFinal = categoryText; }
				
						// 3. Detectar si hi ha resultat (per evitar el 0-0 si encara no s'ha jugat)
						let rawResL = match.local.marcador; 
						let rawResV = match.visitant.marcador;
						// Si és null o undefined, ho convertim a string buit per comparar bé
						if(rawResL === null || rawResL === undefined) rawResL = "";
						if(rawResV === null || rawResV === undefined) rawResV = "";
						
						// Només considerem que "Hi ha Resultat" si algun dels dos camps té valor (encara que sigui 0)
						let hasResult = (rawResL !== "") || (rawResV !== "");
				
						// Textos antics de descans (per compatibilitat)
						let textDescans = ""; 
						if (match.calendari && match.calendari.text_descans && typeof match.calendari.text_descans === 'string') { 
							textDescans = match.calendari.text_descans.toUpperCase(); 
						}
				
						// 4. LÒGICA VISUAL DEL MARCADOR
						let scoreHtml = '';
				
						// A) Està marcat com a SUSPÈS? (Prioritat màxima)
						if (match.calendari.es_suspes) { 
							scoreHtml = '<div class="sr-score-box ajornat" style="color:#d00!important">SUSPÈS</div>'; 
						} 
						// B) Està marcat com a AJORNAT?
						else if (match.calendari.es_ajornat) { 
							scoreHtml = '<div class="sr-score-box ajornat" style="color:#d00!important">AJORN.</div>'; 
						}
						// C) Està per confirmar?
						else if (match.calendari.per_confirmar) { 
							scoreHtml = '<div class="sr-score-box ajornat">CONF.</div>'; 
						}
						// D) Compatibilitat antiga (si algú ha escrit "AJORN" a mà al camp de text)
						else if (match.info.posicio === 'ajornat' || match.info.marcador === 'AJORN.' || textDescans.includes('AJORN')) { 
							scoreHtml = '<div class="sr-score-box ajornat">AJORN.</div>'; 
						} 
						// E) NO hi ha resultat? => MOSTRA L'HORARI (Aquí arreglem el bug del 0-0)
						else if (!hasResult) {
							let timeStr = '00:00'; 
							if (match.calendari.data_iso && match.calendari.data_iso.includes('T')) { 
								timeStr = match.calendari.data_iso.split('T')[1].substring(0, 5); 
							}
							scoreHtml = `<div class="sr-score-box"><span>${timeStr}</span></div>`;
						} 
						// F) SI hi ha resultat => MOSTRA EL MARCADOR
						else {
							let resL = parseInt(rawResL) || 0; 
							let resV = parseInt(rawResV) || 0; 
							let styleL = 'color:white;', styleV = 'color:white;';
							
							// Ressaltar guanyador en groc si és el Mataró
							if (isLocalMataro && resL > resV) { styleL = 'color:#FFD700;'; } 
							else if (isVisitMataro && resV > resL) { styleV = 'color:#FFD700;'; }
							
							scoreHtml = `<div class="sr-score-box"><span style="${styleL}">${resL}</span><span style="color:white; margin:0 2px;">-</span><span style="${styleV}">${resV}</span></div>`;
						}
				
						// 5. Construcció final de la fila (Neta, sense classes de colors al fons)
						html += `<div class="sr-match-row"><div class="sr-team local"><span contenteditable="true">${nomLocalFinal}</span><img src="${match.local.escut}" class="sr-team-logo" onerror="this.style.opacity=0"></div>${scoreHtml}<div class="sr-team visit"><img src="${match.visitant.escut}" class="sr-team-logo" onerror="this.style.opacity=0"><span contenteditable="true">${nomVisitantFinal}</span></div></div>`;
					});
					return html;
				}
				function sr_get_mesurador() {
					let $m = $('#sr-slide-mesurador');
					if ($m.length) return $m;
					$m = $(`<div id="sr-slide-mesurador" style="position:absolute; left:-99999px; top:-99999px; width:540px; z-index:-1; visibility:hidden; pointer-events:none;"><div class="sr-slide ${formatClass}" id="sr-slide-mesurador-inner">${iconHtml}<div class="sr-content-layer"><div class="sr-page-header"><div class="sr-ph-super"></div><div class="sr-ph-title">${mainTitleText} JORNADA</div><div class="sr-ph-date"></div></div><div class="sr-match-list"></div><div class="sr-pagination-wrapper"><span class="sr-pagination-num"><b>1</b>/99</span><span class="sr-arrow-interior dashicons dashicons-arrow-right-alt2"></span></div></div></div></div>`);
					$('body').append($m); return $m;
				}
				function sr_bottom_contingut_real($list) { try { const elList = $list && $list[0] ? $list[0] : null; if (!elList) return null; const ultim = elList.lastElementChild; if (!ultim) return null; const r = ultim.getBoundingClientRect(); return r ? r.bottom : null; } catch(e) { return null; } }
				function sr_cab_sense_tocar_paginacio(opts) {
					const margeSeguretatPx = 10; const $m = sr_get_mesurador(); const $inner = $m.find('#sr-slide-mesurador-inner'); const $super = $inner.find('.sr-ph-super'); const $date = $inner.find('.sr-ph-date'); const $list = $inner.find('.sr-match-list'); const $pag = $inner.find('.sr-pagination-wrapper');
					$super.removeClass('school-mode'); if (opts.superClass) $super.addClass(opts.superClass); $super.text(opts.superText || ''); $date.text(opts.dateText || ''); $list.html(opts.matchesHtml || '');
					const pagRect = $pag[0].getBoundingClientRect(); const topLimit = pagRect.top - margeSeguretatPx; const bottomReal = sr_bottom_contingut_real($list); if (bottomReal === null) return true; return (bottomReal <= topLimit);
				}
				function sr_netejar_separadors_extrems(items) { if (!Array.isArray(items)) return items; while (items.length > 0 && items[0] && items[0].is_separator) { items.shift(); } while (items.length > 0 && items[items.length - 1] && items[items.length - 1].is_separator) { items.pop(); } return items; }
				function sr_crear_slide_buida(type, diaIso) { let dateText = ''; if (diaIso) { let d = new Date(diaIso); let weekday = d.toLocaleDateString('ca-ES', { weekday: 'long' }); weekday = weekday.charAt(0).toUpperCase() + weekday.slice(1); let dayNum = d.getDate(); let month = d.toLocaleDateString('ca-ES', { month: 'long' }); month = month.charAt(0).toUpperCase() + month.slice(1); dateText = `${weekday}, ${dayNum} de ${month}`; } return { type: type, dateText: dateText, matches: [] }; }
				function sr_slide_hi_cap(slideObj) { let superText = ''; let superClass = ''; if (slideObj.type === 'escola') { superText = "Escola d'Handbol"; superClass = 'school-mode'; } const html = sr_render_matches_html(slideObj.matches); return sr_cab_sense_tocar_paginacio({ superText, superClass, dateText: slideObj.dateText || '', matchesHtml: html }); }
				function sr_get_dia_iso_de_match(match) { try { return match && match.calendari && match.calendari.data_iso ? match.calendari.data_iso.split('T')[0] : ''; } catch(e) { return ''; } }
				function sr_get_dia_de_slide(slideObj) { if (!slideObj || !slideObj.matches) return ''; const primerReal = slideObj.matches.find(x => x && !x.is_separator); if (!primerReal) return ''; return sr_get_dia_iso_de_match(primerReal); }
				function sr_recalcular_header_slide(slideObj) {
					if (!slideObj) return;
					const primerReal = slideObj.matches.find(x => x && !x.is_separator);
					if (primerReal) {
						const diaIso = sr_get_dia_iso_de_match(primerReal);
						if (diaIso) {
							let d = new Date(diaIso); let weekday = d.toLocaleDateString('ca-ES', { weekday: 'long' }); weekday = weekday.charAt(0).toUpperCase() + weekday.slice(1);
							let dayNum = d.getDate(); let month = d.toLocaleDateString('ca-ES', { month: 'long' }); month = month.charAt(0).toUpperCase() + month.slice(1);
							slideObj.dateText = `${weekday}, ${dayNum} de ${month}`;
						}
					}
				}
				function sr_balancejar_pagines(slidesArray) {
					if (!Array.isArray(slidesArray) || slidesArray.length === 0) return slidesArray;
					slidesArray.forEach(s => sr_recalcular_header_slide(s));
					for (let i = 0; i < slidesArray.length; i++) {
						let slideActual = slidesArray[i]; if (!slideActual || !Array.isArray(slideActual.matches)) continue;
						sr_netejar_separadors_extrems(slideActual.matches); sr_recalcular_header_slide(slideActual);
						if (slideActual.matches.length === 0) { slidesArray.splice(i, 1); i--; continue; }
						while (!sr_slide_hi_cap(slideActual)) {
							const reals = slideActual.matches.filter(x => x && !x.is_separator);
							if (reals.length <= 1 && slideActual.matches.length <= 1) { break; }
							let elementMogut = null; sr_netejar_separadors_extrems(slideActual.matches);
							while (slideActual.matches.length > 0) { const last = slideActual.matches[slideActual.matches.length - 1]; slideActual.matches.pop(); if (last && !last.is_separator) { elementMogut = last; break; } }
							if (!elementMogut) { break; }
							sr_netejar_separadors_extrems(slideActual.matches); sr_recalcular_header_slide(slideActual);
							let indexSeguent = i + 1; let slideSeguent = slidesArray[indexSeguent] || null;
							const diaActual = sr_get_dia_de_slide(slideActual); const tipus = slideActual.type;
							if (tipus === 'normal') {
								if (!slideSeguent) { slideSeguent = sr_crear_slide_buida('normal', diaActual); slidesArray.splice(indexSeguent, 0, slideSeguent); } 
								else { const diaSeguent = sr_get_dia_de_slide(slideSeguent); if (slideSeguent.type !== 'normal' || (diaSeguent && diaSeguent !== diaActual)) { slideSeguent = sr_crear_slide_buida('normal', diaActual); slidesArray.splice(indexSeguent, 0, slideSeguent); } }
							} else {
								if (!slideSeguent || slideSeguent.type !== 'escola') { slideSeguent = sr_crear_slide_buida('escola', ''); slidesArray.splice(indexSeguent, 0, slideSeguent); }
							}
							if (!Array.isArray(slideSeguent.matches)) slideSeguent.matches = [];
							slideSeguent.matches.unshift(elementMogut);
							sr_netejar_separadors_extrems(slideSeguent.matches); sr_recalcular_header_slide(slideSeguent);
							if (slideActual.matches.length === 0) { slidesArray.splice(i, 1); i--; break; }
						}
					}
					slidesArray = slidesArray.filter(s => s && Array.isArray(s.matches) && s.matches.length > 0);
					slidesArray.forEach(s => { sr_netejar_separadors_extrems(s.matches); sr_recalcular_header_slide(s); });
					return slidesArray;
				}
				let slidesNormal = createLinearSlides(matchesNormal, 'normal'); let slidesEscola = createLinearSlides(matchesEscola, 'escola');
				slidesNormal = sr_balancejar_pagines(slidesNormal); slidesEscola = sr_balancejar_pagines(slidesEscola);
				let finalSlides = slidesNormal.concat(slidesEscola); let totalSlidesCount = finalSlides.length;
				
				finalSlides.forEach((slideData, index) => {
					let slideIndex = index + 1; let superHeaderText = ""; let superClass = ""; 
					let slideType = "general"; // Default
					if(slideData.type === 'escola') { superHeaderText = "Escola d'Handbol"; superClass = "school-mode"; slideType = "escola"; }
					let matchesHtml = sr_render_matches_html(slideData.matches);
					let arrowHtml = (slideIndex < totalSlidesCount) ? '<span class="sr-arrow-interior dashicons dashicons-arrow-right-alt2"></span>' : '';
					
					// AFEGIT: Botó Sticker individual a les accions (btn-open-stickers-slide)
					let slideHtml = `
					<div class="sr-slide-wrapper mode-text">
						<div class="sr-slide ${formatClass} ${extraClasses}" id="slide-${slideIndex}" data-slide-type="${slideType}">
							${iconHtml}
							${textureHtml}
							<div class="sr-bg-container">
								<div class="sr-bg-img" style="background-image: url('${coverImg}'); transform: translate(0px, 0px) scale(1);" data-scale="1"></div>
							</div>
							
							<div class="sr-stickers-layer-bottom"></div>
	
							<div class="sr-content-layer">
								<div class="sr-page-header">
									<div class="sr-ph-super ${superClass}" contenteditable="true">${superHeaderText}</div>
									<div class="sr-ph-title" contenteditable="true">${mainTitleText} JORNADA</div>
									<div class="sr-ph-date" contenteditable="true">${slideData.dateText}</div>
								</div>
								<div class="sr-match-list">${matchesHtml}</div>
								<div class="sr-pagination-wrapper">
									<span class="sr-pagination-num"><b>${slideIndex}</b>/${totalSlidesCount}</span>
									${arrowHtml}
								</div>
							</div>
	
							<div class="sr-stickers-layer-top"></div>
	
						</div>
						<div class="sr-actions">
							<div class="sr-actions-row">
								<div class="sr-mode-switcher">
									<button class="sr-mode-btn active" data-mode="text"><span class="dashicons dashicons-edit"></span> Text</button>
									<button class="sr-mode-btn" data-mode="stickers"><span class="dashicons dashicons-smiley"></span> Stickers</button>
									<button class="sr-mode-btn" data-mode="image"><span class="dashicons dashicons-format-image"></span> Fons</button>
								</div>
								<label class="sr-select-label"><input type="checkbox" class="chk-select-slide" value="#slide-${slideIndex}"> Seleccionar</label>
							</div>
							<div class="sr-actions-row">
								<button class="button btn-change-bg" data-target="#slide-${slideIndex}">Canviar Foto</button>
							</div>
							<div class="sr-actions-row">
								<div class="sr-zoom-control">
									<span class="dashicons dashicons-search"></span>
									<input type="range" class="zoom-range" min="0.1" max="5" step="0.1" value="1" data-target="#slide-${slideIndex}">
									<button class="button btn-apply-all" data-target="#slide-${slideIndex}" title="Aplicar a altres"><span class="dashicons dashicons-controls-repeat"></span></button>
								</div>
							</div>
							<button class="button button-primary download-btn" data-target="slide-${slideIndex}" style="width:100%">Descarregar Pàg. ${slideIndex}</button>
						</div>
					</div>`;
					area.append(slideHtml);
	
					// AUTO-LOAD STICKERS INTERN
					if(SAVED_CONFIG[slideType]) {
						SAVED_CONFIG[slideType].forEach(s => addStickerToSlide($('#slide-'+slideIndex), s.url, s));
					}
				});
				sr_actualitzar_valors_ui(); 
			}
			// --- AUTO-AJUST INTEL·LIGENT DE FONS (Observer) ---
			// Vigila quan canvia la imatge i ajusta la mida per omplir sense espais (Cover) en caixa de 300%
			// --- AUTO-AJUST INTEL·LIGENT DE FONS (Observer Píxel Perfect) ---
			// Calcula els píxels exactes per omplir la diapositiva (NO el contenidor 300%)
			function sr_apply_smart_fit($el, force = false) {
				if ($el.data('sizing-lock')) return;
			
				const bg = $el.css('background-image');
				// Neteja URL robusta
				let src = bg.replace(/^url\(['"]?/, '').replace(/['"]?\)$/, '');
				if (!src || src === 'none' || src.includes('data:image/gif')) return;
			
				// Si ja tenim la foto quadrada i no forcem, no fem res (per respectar si has mogut el zoom manualment)
				const lastBg = $el.data('last-bg');
				if (!force && lastBg === src) return;
			
				// Bloquegem
				$el.data('sizing-lock', true);
			
				const img = new Image();
				img.onload = function() {
					const $slide = $el.closest('.sr-slide');
					if(!$slide.length) return;
			
					// MATEMÀTICA DIRECTA
					const isStory = $slide.hasClass('format-story');
					const cw = 540;
					const ch = isStory ? 960 : 675;
					const iw = this.naturalWidth;
					const ih = this.naturalHeight;
			
					// Càlcul precís
					const scale = Math.max(cw / iw, ch / ih);
					const finalW = Math.ceil(iw * scale);
					const finalH = Math.ceil(ih * scale);
					
					// Aplicar
					$el.css('background-size', finalW + 'px ' + finalH + 'px');
					$el.css('background-position', 'center center');
					$el.css('transform', 'translate(0px, 0px) scale(1)');
					$el.attr('data-scale', 1);
			
					// Sincronitzar Slider
					const $zoomInput = $(`.zoom-range[data-target="#${$slide.attr('id')}"]`);
					if($zoomInput.length) $zoomInput.val(1);
			
					// Guardar estat
					$el.data('last-bg', src);
					$el.data('sizing-lock', false);
				};
				img.src = src;
			}
			
			// Observer 1: Detecta canvis d'estil (Canvi de foto manual o Drag&Drop)
			const styleObserver = new MutationObserver(function(m) { 
				// Quan movem, canvia l'estil, però sr_apply_smart_fit detectarà que és la mateixa foto i no farà res.
				m.forEach(x => sr_apply_smart_fit($(x.target), false)); 
			});
			
			// Observer 2: Detecta canvi de format (Portrait <-> Story)
			const sizeObserver = new MutationObserver(function(m) {
				m.forEach(function(rec) {
					if(rec.target && rec.attributeName === 'class') {
						// Aquí passem TRUE per forçar el re-càlcul encara que la foto sigui la mateixa
						$('.sr-bg-img').each(function(){ sr_apply_smart_fit($(this), true); });
					}
				});
			});
			
			function setupObserversForImage(imgEl) {
				const $img = $(imgEl);
				const $slide = $img.closest('.sr-slide');
				
				// Primera vegada: Forcem l'ajust (true)
				sr_apply_smart_fit($img, true); 
				
				styleObserver.observe(imgEl, { attributes: true, attributeFilter: ['style'] }); 
				
				if($slide.length && !$slide.data('obs-class')) {
					sizeObserver.observe($slide[0], { attributes: true, attributeFilter: ['class'] });
					$slide.data('obs-class', true);
				}
			}
			
			// Observer 3: Detecta càrrega AJAX (noves slides)
			const domObserver = new MutationObserver(function(mutations) {
				mutations.forEach(function(m) {
					m.addedNodes.forEach(function(node) {
						if (node.nodeType === 1) { 
							const $imgs = $(node).hasClass('sr-bg-img') ? $(node) : $(node).find('.sr-bg-img');
							$imgs.each(function() { setupObserversForImage(this); });
						}
					});
				});
			});
			
			const previewArea = document.getElementById('preview-area');
			if(previewArea) domObserver.observe(previewArea, { childList: true, subtree: true });
			$('.sr-bg-img').each(function() { setupObserversForImage(this); });
		});
		</script>
		<?php
	}
	
	/**
	 * 3. AJAX PER GUARDAR STICKERS GLOBALS (NOU)
	 */
	add_action('wp_ajax_sr_save_stickers_global', function() {
		// Comprovació de seguretat bàsica
		if (!current_user_can('edit_posts')) wp_send_json_error('No permès');
	
		$type = sanitize_text_field($_POST['slide_type']);
		$stickers_data = isset($_POST['stickers']) ? $_POST['stickers'] : array();
	
		// Sanitització bàsica de l'array
		$clean_stickers = array();
		if(is_array($stickers_data)) {
			foreach($stickers_data as $s) {
				$clean_stickers[] = array(
					'url'      => esc_url_raw($s['url']),
					'top'      => sanitize_text_field($s['top']),
					'left'     => sanitize_text_field($s['left']),
					'width'    => sanitize_text_field($s['width']),
					'rotation' => floatval($s['rotation']),
					'scale'    => floatval($s['scale']),
					'flip'     => intval($s['flip']),
					'layer'    => sanitize_text_field($s['layer'])
				);
			}
		}
	
		// Recuperem la config actual i actualitzem només el tipus que toca (Cover, Escola, etc.)
		$current_config = get_option('sr_stickers_global_config', array());
		$current_config[$type] = $clean_stickers;
	
		update_option('sr_stickers_global_config', $current_config);
	
		wp_send_json_success('Configuració de stickers guardada per: ' . $type);
	});