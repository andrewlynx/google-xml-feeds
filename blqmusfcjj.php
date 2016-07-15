<?php/*Plugin Name: Google shopping feedsVersion:          		1.1.0Author:           		Andrew MelnikVersion 1.0.0 author: 	Tarik A. */ if (!defined('ABSPATH')) {	exit; // Exit if accessed directly}function blqmusfcjj_generate_XML($manual=false) {	$admin_email = get_option( 'admin_email' );	//wp_mail( $admin_email, 'Google shopping feeds', 'Generation started at : '.date('Y-m-d H:i:s'));		$google_attributes=get_option('blqmusfcjj_google_attributes');	$google_dynamic_attributes=get_option('blqmusfcjj_google_dynamic_attributes');		remove_filter('woocommerce_get_regular_price','gvojdzycei_woocommerce_converted_prices',10,2);	remove_filter('woocommerce_get_sale_price','gvojdzycei_woocommerce_converted_prices',10,2);	remove_filter('woocommerce_get_price','gvojdzycei_woocommerce_converted_prices',10,2);  	$query = new WP_Query(array(		'post_type' => 'product',		'post_status' => 'publish',		'fields' => 'ids',		'posts_per_page' => -1,	)	);	if ($query->have_posts()) {		$countries = array();		$target_countries = plugin_dir_path(__FILE__) . 'target_countries.csv';		if(file_exists($target_countries)){			$first_line = true;			if (($handle = fopen($target_countries, "r")) !== FALSE) {				while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {					if($first_line) { $first_line = false; continue; }					$countries[$data[0]]=array(						'tax_rate'=>$data[1],						'tax_ship'=>$data[2],						);				}				fclose($handle);			}		}		if($manual == true){			if(!empty (get_option('update_languages')) ){				$country = get_option('update_languages', false);				$countries = array(					$country => array(					'tax_rate'=>0,					'tax_ship'=>'n',					)				);			}		}		if(empty($countries)){			$countries=array(				'US'=>array(					'tax_rate'=>0,					'tax_ship'=>'n',					)				);		}		$product_details = array();		foreach ($query->posts as $product_id) {			$post = get_post($product_id);			$product = get_product($product_id);			$product_cats = wp_get_post_terms($product_id, 'product_cat');			$product_type = $product_cats[0]->name;			$category = $product_cats[0]->term_id; 			$attachment_ids = $product->get_gallery_attachment_ids();			$weight_unit = get_option( 'woocommerce_weight_unit' );			$dimension_unit = get_option( 'woocommerce_dimension_unit' );			$sale_price_dates_from = get_post_meta($product_id,'_sale_price_dates_from',true);			if($sale_price_dates_from) { $sale_price_dates_from=date_i18n('c', $sale_price_dates_from); }						$sale_price_dates_to = get_post_meta($product_id,'_sale_price_dates_to',true);			if($sale_price_dates_to) { $sale_price_dates_to=date_i18n('c', $sale_price_dates_to); }						$regular_price = $product->get_regular_price();			$sale_price = $product->get_sale_price();			if( $product->product_type == 'variable' ) {								$available_variations = $product->get_available_variations();				$variation_id=$available_variations[0]['variation_id'];				$variable_product1= new WC_Product_Variation( $variation_id );				$regular_price = $variable_product1->get_regular_price();				$sale_price = $variable_product1->get_sale_price();			}			$details = array(				'id' => $product_id,				'post_id' => $product_id,				'title' => $post->post_title,				'additional_image_link' => array(),				'description' => $post->post_excerpt,				'image_link' => wp_get_attachment_url(get_post_thumbnail_id($product_id, 'thumbnail')),				'link' => get_the_permalink($product_id),				'mpn' => $product->get_sku(),				'price' => $regular_price,				'sale_price' => $sale_price,				'availability' => $product->is_in_stock() ? 'in stock' : 'out of stock',				'product_type' => $product_type,				'category' =>$category,				/*'shipping_weight' => $product->weight .' '.$weight_unit,				'shipping_width' => $product->width .' '.$dimension_unit,				'shipping_height' => $product->height .' '.$dimension_unit,				'shipping_length' => $product->length .' '.$dimension_unit,*/			);			if($sale_price_dates_from && $sale_price_dates_to){				$details['sale_price_effective_date']=$sale_price_dates_from.'/'.$sale_price_dates_to;			}			$details['mobile_link']=$details['link'];					foreach ($attachment_ids as $attachment_id) {				$details['additional_image_link'][] = wp_get_attachment_url($attachment_id);				$details['additional_image_link'] = array_slice($details['additional_image_link'], 0, 9);			}			$product_details[] = $details;		}		$feed_categories = get_option('google_feeds_select');		chmod(plugin_dir_path(__FILE__) . 'feeds', 0755); 		@unlink(plugin_dir_path(__FILE__) . 'feeds/list.txt');		$title=get_bloginfo('name');		$sanitized_title=sanitize_title($title);		$link=get_site_url();		$description=get_bloginfo('description');				$default_values= get_option('blqmusfcjj_default_values',array());		$shipping_price= get_option('blqmusfcjj_shipping_price',0);				foreach ($countries as $country_code => $country_value) {									$feed_file = plugin_dir_path(__FILE__) . 'feeds/'.$sanitized_title.'_'.$country_code.'.xml';			$feed_file_url = plugin_dir_url(__FILE__) . 'feeds/'.$sanitized_title.'_'.$country_code.'.xml';			file_put_contents(plugin_dir_path(__FILE__) . 'feeds/list.txt', $feed_file_url . PHP_EOL, FILE_APPEND);			$xml = new DOMDocument("1.0", "UTF-8");			$xml->formatOutput = true;			$rss = $xml->appendChild($xml->createElement('rss'));			$rss->setAttribute('version', '2.0');			$rss->setAttribute('xmlns:g', 'http://base.google.com/ns/1.0');			$channel = $rss->appendChild($xml->createElement('channel'));			$channel->appendChild($xml->createElement('title', $title));			$channel->appendChild($xml->createElement('link', $link));			$channel->appendChild($xml->createElement('description', $description));			$channel->appendChild($xml->createElement('time', date('Y-m-d H:i:s')));					$language_code_by_country=blqmusfcjj_language_code_by_country($country_code);			//var_dump( $language_code_by_country );			$currency_by_country=blqmusfcjj_currency_by_country($country_code);									$converted_shipping_price=0;						if($shipping_price){				$converted_shipping_price=apply_filters( 'blqmusfcjj_prices', $shipping_price,$currency_by_country);			}						foreach ($product_details as $product) {							$hide_from_google_feeds= get_post_meta($post->ID, 'iqxzvqhmye_hide_from_google_feeds', true );				if($hide_from_google_feeds=='yes'){					continue;				} 				if(empty($converted_shipping_price)){					$prices= iqxzvqhmye_prices($product['post_id'],$country_code,false,false);					if($prices && isset($prices['delivery_cost'])){												$converted_shipping_price=apply_filters( 'blqmusfcjj_prices',$prices['delivery_cost'],strtoupper($currency_by_country));											}else{						continue;					}				}								$cat = $product['product_type'];								$item = $channel->appendChild($xml->createElement('item'));								 				foreach ( $google_attributes as $attribute_key => $attribute_value ) {										if($attribute_key=='tax'){							$tax = $item->appendChild($xml->createElement('g:tax'));						$tax->appendChild($xml->createElement('g:country',$country_code));						$tax->appendChild($xml->createElement('g:rate',$country_value['tax_rate']));						$tax->appendChild($xml->createElement('g:tax_ship',$country_value['tax_ship']));					}else if($attribute_key=='shipping'){						$shipping = $item->appendChild($xml->createElement('g:shipping')); 						$shipping->appendChild($xml->createElement('g:country',$country_code));						$shipping->appendChild($xml->createElement('g:price',						$converted_shipping_price.' '.strtoupper($currency_by_country))); 					}else if($attribute_key=='google_product_category'){												var_dump($product['category']);  						if (!empty($feed_categories) && isset($feed_categories[$product['category']])) {							$item->appendChild($xml->createElement('g:google_product_category', $feed_categories[$product['category']]));						}					}else if($attribute_key=='additional_image_link'){												if(!empty($product[$attribute_key])){							foreach ($product['additional_image_link'] as $image_url) {								$item->appendChild($xml->createElement('g:additional_image_link', $image_url));							}						}					}else if(isset($product[$attribute_key])){												if(!empty($product[$attribute_key])){							if($attribute_key=='id'){																$product[$attribute_key]=$country_code.'-'.$product[$attribute_key];															}else if($attribute_key=='link' || $attribute_key=='mobile_link' ){																$site_url=get_site_url();								$product[$attribute_key]=str_replace($site_url,$site_url.'/'.$language_code_by_country,$product[$attribute_key]);								$product[$attribute_key]=$product[$attribute_key].'?currency='.strtoupper($currency_by_country);															}else if($attribute_key=='title' || $attribute_key=='description'){																								$saved_translation=null;																if(!$manual){									$saved_translation=get_post_meta($product['post_id'],'blqmusfcjj_'.$attribute_key.'_'.$language_code_by_country,true);								} 																if($saved_translation){									$product[$attribute_key]=$saved_translation;								}else{									$product[$attribute_key]=trim(str_replace(array('[:en]','[:]'),'',$product[$attribute_key]));									$product[$attribute_key]=blqmusfcjj_translator($product[$attribute_key],$language_code_by_country);									update_post_meta($product['post_id'],'blqmusfcjj_'.$attribute_key.'_'.$language_code_by_country,$product[$attribute_key]);								}																							}else if($attribute_key=='price' || $attribute_key=='sale_price' ){															$product[$attribute_key]=apply_filters( 'blqmusfcjj_prices', $product[$attribute_key], strtoupper($currency_by_country)) . ' ' .strtoupper($currency_by_country);								$product[$attribute_key]=$product[$attribute_key];									}														if($attribute_value['cdata']){								$cdata_child = $item->appendChild($xml->createElement('g:'.$attribute_key));								$cdata_child->appendChild($xml->createCDATASection($product[$attribute_key]));							}else{								$item->appendChild($xml->createElement('g:'.$attribute_key, $product[$attribute_key]));							}						}					}else if( !empty($default_values[ $cat ][$attribute_key ] ) || !empty( $google_attributes[ $cat ]['attribute'][$attribute_key ] ) ){																	$value = $google_attributes[ $cat ]['attribute'][$attribute_key];													$product_item = new WC_Product($product['post_id']);						$product_attribute = $product_item->get_attribute('pa_'.$value);						$default = $default_values[$cat][$attribute_key];						if(empty($product_attribute)){							if($attribute_key=='condition'){								$product_attribute='New';							} else if ( !empty( $google_attributes[ $cat ][ $attribute_key ] ) ){								$product_attribute = $google_attributes[ $cat ]['attribute'][ $attribute_key ];															} 						}						if(!empty($default)){							$product_attribute .= ' ' . $default;						}						if($product_attribute){							$product_attribute=str_replace(', ', '/', $product_attribute);							$item->appendChild($xml->createElement('g:'.$attribute_key,$product_attribute ));						}					}				} 								if(!$shipping_price){					$converted_shipping_price=0;				}										}			echo $xml->save($feed_file);			//file_put_contents($feed_file, $xml);		}	}	wp_reset_postdata();		//wp_mail( $admin_email, 'Google shopping feeds', 'Generation finished at : '.date('Y-m-d H:i:s')); }function blqmusfcjj_twaicejoop_prices($price, $currency = false){		$price=floatval($price);		if(!$currency){		$currency = get_woocommerce_currency();	}		if($currency!='USD'){				$resources=get_option('gvojdzycei_resources');		if(!$resources){			$resources=gvojdzycei_yahoo_finance();		}				$price=$price*$resources[$currency]; 				$rate=get_option('gvojdzycei_currency_converter_rate'); 		if($rate){			$price = $price + ($price*$rate/100);		}	}		$price = number_format ( $price, 2 );	return apply_filters( 'gvojdzycei_price_format', $price );	}add_filter('blqmusfcjj_prices', 'blqmusfcjj_twaicejoop_prices', 10, 2); function blqmusfcjj_admin_menu() {	add_menu_page('Google feeds', 'Google feeds', 'administrator', 'blqmusfcjj_settings', 'blqmusfcjj_settings_page', '');}add_action('admin_menu', 'blqmusfcjj_admin_menu');function blqmusfcjj_settings_page() {	$google_attributes=get_option('blqmusfcjj_google_attributes');	$google_dynamic_attributes=get_option('blqmusfcjj_google_dynamic_attributes');	if (isset($_POST['POST_blqmusfcjj_save_settings'])) {		if(isset($_POST['blqmusfcjj_google_categories_opt'])){			update_option('blqmusfcjj_google_categories_opt', $_POST['blqmusfcjj_google_categories_opt']);		}		if(isset($_POST['blqmusfcjj_attributes'])){			foreach ($_POST['blqmusfcjj_attributes'] as $attribute_key => $attribute_value) {				$google_attributes[$attribute_key]['attribute']=$attribute_value;				update_option('blqmusfcjj_google_attributes',$google_attributes);			}		}	}		if(isset($_POST['google_feeds_select'])){		$old_options = get_option('google_feeds_select');		$cat = $_POST['woocommerce_select'];		$opt = $_POST['google_feeds_select'];		if( is_array($old_options) ){			foreach($old_options as $old_cat => $value){				if($old_cat == $cat){					$value = $opt;				}				$new_options[$old_cat] = $value;			}		}				$new_options[$cat] = $opt; 		update_option('google_feeds_select',$new_options);	}		if(isset($_POST['blqmusfcjj_api_key'])){		update_option('blqmusfcjj_api_key',sanitize_text_field($_POST['blqmusfcjj_api_key']));	}		if(isset($_POST['default_values'])){		update_option('blqmusfcjj_default_values',$_POST['default_values']);	}		if(isset($_POST['blqmusfcjj_shipping_price'])){		update_option('blqmusfcjj_shipping_price',floatval($_POST['blqmusfcjj_shipping_price']));	}		if (isset($_POST['POST_blqmusfcjj_generate_XML'])) {		blqmusfcjj_generate_XML(true);	}		if (isset($_POST['update_languages'])){		update_option('update_languages',$_POST['update_languages']);	}		$google_categories = get_option('blqmusfcjj_google_categories_opt');	$default_values= get_option('blqmusfcjj_default_values',array());	$shipping_price= get_option('blqmusfcjj_shipping_price',0);	$google_hidden_attributes=array();	$attribute_taxonomies=wc_get_attribute_taxonomies();	?>		<div class="wrap">	<?php $opt = get_option('google_feeds_select'); 	?>	<script>		(function($) {		    'use strict';		    $(function() {				$( ".blqmusfcjj_categories" ).each(function() {					var main=$(this);					var feed_name=$(this).attr('data-feed-name');					main.find('.feeds_select').hide();					var input=null;					var id=null;					var cat=null;					var opt=<?php echo json_encode($opt); ?>; 					var options = $('.cat-options');																$(this).find('#woocommerce_select').change(function() {						id=$(this).val();						main.find('input').hide(); 						if(id){ 							main.find('.feeds_select').show();							$('[name=google_feeds_select]').val( opt[id] );  						}else{							main.find('.feeds_select').hide();						} 						$(options).hide();						var current = $( '#woocommerce_select option[value="' + id + '"]').text();						console.log( current );  						$('#' + current ).show();					});					$(this).find('.feeds_select').change(function() {						cat=$(this).val();						if(cat && input){							input.val($(this).val());						}					});											});			});		})(jQuery);	</script>	<style>		.blqmusfcjj_categories input{			display: none;		}		.blqmusfcjj_categories .feeds_select{			width: 80%;			font-size: 10px;			display: none;		}	</style>	    <h2>Google feeds Settings</h2>    <hr>    <p>    	<?php echo '<a target="_target" href="' . plugin_dir_url(__FILE__) . 'feeds/list.txt">View generated feeds</a>'; ?>    </p>	<p>		<form method="post" >			<table class="form-table">				<tbody>							<tr>					<th scope="row">						<label for="">Google translate API key</label>					</th>					<td>						<input class="regular-text" type="text" name="blqmusfcjj_api_key" value="<?php echo get_option("blqmusfcjj_api_key"); ?>">						<p class="description">If empty the content will be in English for all countries.</p>					</td>				</tr>				<tr>					<th scope="row">						<label for="">Shipping price</label>					</th>					<td>						<input class="regular-text" type="number" step="0.01" name="blqmusfcjj_shipping_price" value="<?php echo $shipping_price; ?>">						<p class="description">If empty, shipping costs will be fetched from shipping table for each product.</p>					</td>				</tr>								<tr>					<th scope="row">						<label for="">Update Languages</label>					</th>					<td>						<?php 						$countries = array();						$target_countries = plugin_dir_path(__FILE__) . 'target_countries.csv';						if(file_exists($target_countries)){							$first_line = true;							if (($handle = fopen($target_countries, "r")) !== FALSE) {								while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {									if($first_line) { $first_line = false; continue; }									$countries[$data[0]]=array(										'tax_rate'=>$data[1],										'tax_ship'=>$data[2],										);								}								fclose($handle);							}						}						?>						<select name="update_languages" class="feeds_select">						<option value="">All Countries</option>						<?php  							$selected = get_option('update_languages');							foreach( $countries as $country => $data ) {								echo '<option '. selected( $selected, $country ) .' value="'.$country.'">'. $country.'</option>';							}													?>						<p class="description">Update translation for country</p>					</td>				</tr>				<tr>					<th scope="row">						<label for="">Taxonomies</label>					</th>					<td>				    <?php						$categories = get_categories(array(							'taxonomy' => 'product_cat',							'orderby' => 'name',							'show_count' => 0,							'pad_counts' => 0,							'hierarchical' => 1,							'hide_empty' => 0,						));						if ($categories) {							$category_file = plugin_dir_path(__FILE__) . 'google_taxonomies.txt';							$handle = fopen($category_file, "r");							echo '<div class="blqmusfcjj_categories" data-feed-name="google">';							echo '<p>';								echo '<select name="woocommerce_select" id="woocommerce_select">';									echo '<option value="">Select a category</option>';									foreach ($categories as $category) {										echo ' <option value="' . $category->term_id . '">' . $category->name . '</option>';									}								echo '</select>';							echo '</p>';							echo '<p>';							echo '<select name="google_feeds_select" class="feeds_select">';							echo '<option value="">Select a category</option>';							while (($line = fgets($handle)) !== false) {								$data = array();								$line = explode(' - ', $line);								$data['value'] = trim($line[0]);								$data['name'] = trim($line[1]);								$disabled = '';								echo '<option ' . $disabled . ' value="' . $data['value'] . '">' . $data['name'] . '</option>';							}							fclose($handle);							echo '</select>';							echo '</p>';							foreach ($categories as $category) {								$value = '';								if (isset($google_categories[$category->term_id])) {									$value = $google_categories[$category->term_id];								}								echo '<input name="blqmusfcjj_google_categories_opt[' . $category->name . ']" type="hidden" class="regular-text"  value="' . $value . '" />'; ?>								</td>							</tr>																															<tr>									<table class="cat-options form-table" style="display: none;" id="<?php echo $category->name; ?>"> 										<?php foreach ($google_attributes as $attribute_key => $attribute_value) { ?>											<?php if(!in_array($attribute_value['label'], $google_hidden_attributes) && !empty( $attribute_value['label'] ) ){ ?>												<tr>													<th scope="row">														<label for=""><?php echo ucfirst($attribute_value['label']); ?></label>													</th>													<td>														<?php if(in_array($attribute_key, $google_dynamic_attributes)){ ?>															<p>																This attribute will be automatically  filled.															</p>														<?php }else { 															if(!empty($google_attributes[$category->name]['attribute'][$attribute_key])){																$val = $google_attributes[$category->name]['attribute'][$attribute_key];															} else {																$val = $attribute_value['attribute'];															}																//var_dump($google_attributes);															?>															<select name="blqmusfcjj_attributes[<?php echo $category->name ?>][<?php echo $attribute_key; ?>]">																<option value="">Select an attribute</option>																<?php foreach ($attribute_taxonomies as $taxonomie) { ?>																<option <?php selected($val,$taxonomie->attribute_name); ?> value="<?php echo $taxonomie->attribute_name; ?>"><?php echo $taxonomie->attribute_label; ?></option>																<?php } ?>															</select>															<br>															<input value="<?php echo $default_values[$category->name][$attribute_key]; ?>" type="text" class="regular-text" name="default_values[<?php echo $category->name ?>][<?php echo $attribute_key; ?>]" placeholder="Default value (you have to select an attribute)">														<?php } ?>														<p>															<strong><?php echo $attribute_value['status']; ?></strong>														</p>														<p class="description"><?php echo $attribute_value['comment']; ?></p>													</td>												</tr>											<?php } ?>										<?php } ?>									</table>								</tr>							<?php }							echo '</div>';						}					?>					</td>				</tr>				</tbody>			</table>            <input type="submit" name="POST_blqmusfcjj_save_settings" class="button button-primary" value="Save settings">		</form>      	</p>	<hr/>	<p>		<form method="post">            <input type="submit" name="POST_blqmusfcjj_generate_XML" class="button button-primary" value="Refresh Google feeds manually">            <p class="description">				All google feeds are updated automatically once per day.			</p>        </form>	</p></div><?php }register_activation_hook(__FILE__, 'blqmusfcjj_register_activation_hook');function blqmusfcjj_register_activation_hook() {	$google_attributes = array(		"id"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"id",				"status"=>"Required",				"comment"=>"Identifies each product. An ID must be unique to an item across the account, and the product must maintain the same ID over time.",			),		"title"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"title",				"status"=>"Required",				"comment"=>"Product title. No promotional text or shipping information allowed.",			),		"description"=>array(				"cdata"=>true,				"attribute"=>"",				"label"=>"description",				"status"=>"Required",				"comment"=>"Product's description. No promotional text allowed, including shipping information.",			),		"condition"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"condition",				"status"=>"Required (If not set default value will be : 'New')",				"comment"=>"Product's condition or state. Accepted values: New, Refurbished, Used",			),		"price"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"price",				"status"=>"Required",				"comment"=>"Product's base price, along with the corresponding currency code (e.g. USD for US dollars).",			),		"availability"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"availability",				"status"=>"Required",				"comment"=>"Availability status of an item. Accepted values: Preorder, In Stock, Out of Stock",			),		"link"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"link",				"status"=>"Required",				"comment"=>"Product's landing page from Google Shopping. Must reside within the claimed website domain and be reviewable by Google crawlers.",			),		"image_link"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"image link",				"status"=>"Required",				"comment"=>"URL of the main image for a product that’s crawlable by Google.",			),		"gtin"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"gtin",				"status"=>"Required for some categories",				"comment"=>"Product’s Global Trade Item Number (GTIN). Accepted formats: UPC (in North America), EAN (in Europe), JAN (in Japan), ISBN (for books)",			),		"mpn"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"mpn",				"status"=>"Required for some categories",				"comment"=>"Code from the manufacturer that identifies the product. Also known as the 'Manufacturer Part Number'.",			),		"brand"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"brand",				"status"=>"Required for some categories",				"comment"=>"Product's brand name.",			),		"identifier_exists"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"identifier exists",				"status"=>"Required for some categories",				"comment"=>"Used when a product is in a Google product category where unique product identifiers are required, but identifiers don't exist for that product (e.g. custom or antique goods). When there is no unique product identifier available, submit a value of 'false'.",			),		"google_product_category"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"google product category",				"status"=>"Required for some categories",				"comment"=>"The category ID or full path of the product's category from Google's taxonomy.",			),		"is_bundle"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"is bundle",				"status"=>"Required for some categories",				"comment"=>"When the product is bundled with another type of product (e.g. a camera and camera bag). Submit a value of 'true' if the item is a merchant-defined bundle.",			),		"multipack"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"multipack",				"status"=>"Required for some categories",				"comment"=>"Indicates the number of identical products packed together (e.g. 6 pens sold together).",			),		"adult"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"adult",				"status"=>"Required for some categories",				"comment"=>"Indicate that an product is 'adult' per our policies: https://support.google.com/merchants/answer/2953140",			),		"gender"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"gender",				"status"=>"Required for some categories",				"comment"=>"Accepted values: Male, Female, Unisex",			),		"age_group"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"age group",				"status"=>"Required for some categories",				"comment"=>"Accepted values: Newborn, Infant, Toddler, Kids, Adult",			),		"size"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"size",				"status"=>"Required for some categories",				"comment"=>"Product's size.",			),		"color"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"color",				"status"=>"Required for some categories",				"comment"=>"Product's color.",			),		"material"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"material",				"status"=>"Required for some categories",				"comment"=>"Primary material used in product.",			),		"pattern"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"pattern",				"status"=>"Required for some categories",				"comment"=>"Product pattern, such as solid, stripes, or any other value.",			),		"item_group_id"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"item group id",				"status"=>"Required for some categories",				"comment"=>"For an product with multiple colors, sizes, materials, patterns, age groups, genders, size types, or size systems, group them together with a unique 'item group id.’",			),		"tax"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"tax",				"status"=>"Recommended (if applicable)",				"comment"=>"An product-level override for merchant-level tax settings as defined in your Merchant Center account. (US only) Has four sub-attributes: country (optional), geographic region (optional), rate (required), tax_ship (optional)",			),		"shipping"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"shipping",				"status"=>"Recommended (if applicable)",				"comment"=>"An product-level override for merchant-level shipping settings as defined in your Merchant Center account. Has four sub-attributes: country (optional), geographic region (optional), service (optional), price (required)",			),		"shipping_weight"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"shipping weight",				"status"=>"Recommended (if applicable)",				"comment"=>"Weight used to calculate the shipping cost. Mandatory if the account-level shipping setting is based on weight, such as ‘carrier-calculated.’",			),		"shipping_length"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"shipping length",				"status"=>"Recommended (if applicable)",				"comment"=>"Length of the package needed to ship the product. Recommended if you use carrier-calculated rates in your shipping methods. ",			),		"shipping_width"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"shipping width",				"status"=>"Recommended (if applicable)",				"comment"=>"Width of the package needed to ship the product. Recommended if you use carrier-calculated rates in your shipping methods. ",			),		"shipping_height"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"shipping height",				"status"=>"Recommended (if applicable)",				"comment"=>"Height of the package needed to ship the product. Recommended if you use carrier-calculated rates in your shipping methods. ",			),		"shipping_label"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"shipping label",				"status"=>"Recommended (if applicable)",				"comment"=>"Use for custom grouping of products in your shipping rules.",			),		"sale_price"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"sale price",				"status"=>"Recommended (if applicable)",				"comment"=>"Product's temporary sale price. Note that the typical price at the store must still be submitted using the 'price' attribute.",			),		"sale_price_effective_date"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"sale price effective date",				"status"=>"Recommended (if applicable)",				"comment"=>"Date range of the sale. Is used in conjunction with ‘sale price.’  ",			),		"additional_image_link"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"additional image link",				"status"=>"Recommended (if applicable)",				"comment"=>"Up to 10 additional images of the product -- for instance, multiple angles or colors.",			),		"mobile_link"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"mobile link",				"status"=>"Recommended (if applicable)",				"comment"=>"Provide links to mobile-optimized versions of the landing pages for your products. ",			),		"product_type"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"product type",				"status"=>"Recommended (if applicable)",				"comment"=>"Your own categorization for an product. ",			),		"availability_date"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"availability date",				"status"=>"Recommended (if applicable)",				"comment"=>"Recommended for products with the ‘preorder’ value for the ‘availability’ attribute.",			),		"size_type"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"size type",				"status"=>"Recommended (if applicable)",				"comment"=>"The cut of an apparel product. Accepted values: Regular, Petite, Plus, Big and tall, Maternity",			),		"size_system"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"size system",				"status"=>"Recommended (if applicable)",				"comment"=>"The country sizing system of an apparel product. Accepted values: US, UK, EU, DE, FR, JP, CN, IT, BR, MX, AU",			),		"adwords_redirect"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"adwords redirect",				"status"=>"Recommended (if applicable)",				"comment"=>"Specify a separate URL that can be used to track traffic coming from Google Shopping. If provided, you must make sure that the URL will redirect to the same website as given in the ‘link’ or ‘mobile link’ attribute.",			),		"custom_label_0"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"custom label 0",				"status"=>"Recommended (if applicable)",				"comment"=>"Use if you want to subdivide the products in your Shopping campaign using values of your choosing. You can have up to five custom label attributes, numbered 0-4, e.g. 'custom label 1'.",			),		"custom_label_1"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"custom label 1",				"status"=>"Recommended (if applicable)",				"comment"=>"Use if you want to subdivide the products in your Shopping campaign using values of your choosing. You can have up to five custom label attributes, numbered 0-4, e.g. 'custom label 1'.",			),		"custom_label_2"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"custom label 2",				"status"=>"Recommended (if applicable)",				"comment"=>"Use if you want to subdivide the products in your Shopping campaign using values of your choosing. You can have up to five custom label attributes, numbered 0-4, e.g. 'custom label 1'.",			),		"custom_label_3"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"custom label 3",				"status"=>"Recommended (if applicable)",				"comment"=>"Use if you want to subdivide the products in your Shopping campaign using values of your choosing. You can have up to five custom label attributes, numbered 0-4, e.g. 'custom label 1'.",			),		"custom_label_4"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"custom label 4",				"status"=>"Recommended (if applicable)",				"comment"=>"Use if you want to subdivide the products in your Shopping campaign using values of your choosing. You can have up to five custom label attributes, numbered 0-4, e.g. 'custom label 1'.",			),		"unit_pricing_measure"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"unit pricing measure",				"status"=>"Recommended (if applicable)",				"comment"=>"Defines the measure and dimension of a product, e.g. 135floz or 55oz. It's recommended to submit the ‘unit pricing base measure’ attribute together with ‘unit pricing measure’. Accepted values: Weight: oz, lb, mg, g, kg, Volume: floz, pt, qt, gal, ml, cl, l, cbm, Length: in, ft, yd, cm, m, Area: sqft, sqm, Per unit: ct",			),		"unit_pricing_base_measure"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"unit pricing base measure",				"status"=>"Recommended (if applicable)",				"comment"=>"Specifies your preference of the denominator of the unit price (e.g. 100floz). You should only submit this attribute if you also submit ‘unit pricing measure’. Accepted values: Weight: oz, lb, mg, g, kg, Volume: floz, pt, qt, gal, ml, cl, l, cbm, Length: in, ft, yd, cm, m, Area: sqft, sqm, Per unit: ct",			),		"loyalty_points"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"loyalty points",				"status"=>"Recommended (if applicable)",				"comment"=>"Loyalty points with a specific monetary value (Japan only) Has three sub-attributes: name (optional), points value (required), ratio (optional)",			),		"multiple_installments"=>array(				"cdata"=>false,				"attribute"=>"",				"label"=>"multiple installments",				"status"=>"Recommended (if applicable)",				"comment"=>"For products that can be paid for in multiple installments. (Brazil Only) Has two sub-attributes: months (required), amount (required)",			),	);	update_option('blqmusfcjj_google_attributes',$google_attributes);	$google_dynamic_attributes=array('id','title','description','price','product_type','sale_price','sale_price_effective_date','availability','link','image_link','mpn','google_product_category','shipping','shipping_label','additional_image_link','mobile_link','availability_date','adwords_redirect');	update_option('blqmusfcjj_google_dynamic_attributes',$google_dynamic_attributes);	$country_currency_mapping=array();	if (($handle = fopen(plugin_dir_url( __FILE__ ) .'country-code-to-currency-code-mapping.csv', 'r')) !== FALSE) {	    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {	        $country_currency_mapping[$row[0]]=$row[1];	    }	    fclose($handle);	}      update_option( 'blqmusfcjj_country_currency_mapping',$country_currency_mapping);	$country_language_mapping=array();	if (($handle = fopen(plugin_dir_url( __FILE__ ) .'country-code-to-language-code-mapping.csv', 'r')) !== FALSE) {	    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {	        $country_language_mapping[$row[1]]=$row[0];	    }	    fclose($handle);	}      update_option( 'blqmusfcjj_country_language_mapping',$country_language_mapping);   	// blqmusfcjj_generate_XML();		    if (! wp_next_scheduled ( 'blqmusfcjj_daily_event' )) {		wp_schedule_event( '1262304600', 'daily', 'blqmusfcjj_daily_event');    }} add_action('blqmusfcjj_daily_event', 'blqmusfcjj_generate_XML');register_deactivation_hook(__FILE__, 'blqmusfcjj_deactivation');function blqmusfcjj_deactivation() {	wp_clear_scheduled_hook('blqmusfcjj_daily_event');}function blqmusfcjj_currency_by_country($country) {	$country=strtolower($country);	$country_currency_mapping= get_option( 'blqmusfcjj_country_currency_mapping');	if(isset($country_currency_mapping[$country])){		return $country_currency_mapping[$country];	}		return 'USD';}function blqmusfcjj_language_code_by_country($country) {	$country=strtolower($country);	$country_language_mapping= get_option( 'blqmusfcjj_country_language_mapping');	if(isset($country_language_mapping[$country])){		return $country_language_mapping[$country];	}	return 'en';	}function blqmusfcjj_translator($text,$target) {		$api_key=get_option('blqmusfcjj_api_key');		if($api_key && $target!="en"){				$response = wp_remote_post( "https://www.googleapis.com/language/translate/v2", array(			'method' => 'POST',			'headers' => array(				'X-HTTP-Method-Override'=> 'GET',			),			'body' => array(				'key' => $api_key,				'q' => $text,				'source' => 'en',				'target' => $target,				),			)		);		if ( !is_wp_error( $response ) ) {			$body = $response['body'];			$body=json_decode($body);			if(isset($body->data) && isset($body->data->translations) && isset($body->data->translations[0]) && isset($body->data->translations[0]->translatedText)){				return $body->data->translations[0]->translatedText;			}		}	}	return $text;}