<?php

if (!defined('ABSPATH')) {
    exit;
}

class WCMp_Widget_Vendor_Product_Categories extends WC_Widget {

    public $vendor_term_id;

    public function __construct() {
        $this->widget_cssclass = 'wcmp woocommerce wcmp_widget_vendor_product_categories widget_product_categories';
        $this->widget_description = __('Displays a list of product categories added by the vendor on the vendor shop page.', 'dc-woocommerce-multi-vendor');
        $this->widget_id = 'wcmp_vendor_product_categories';
        $this->widget_name = __('WCMp: Vendor\'s Product Categories', 'dc-woocommerce-multi-vendor');
        $this->settings = array(
            'title' => array(
                'type' => 'text',
                'std' => __('Vendor Product categories', 'dc-woocommerce-multi-vendor'),
                'label' => __('Title', 'dc-woocommerce-multi-vendor'),
            ),
            'count' => array(
                'type' => 'checkbox',
                'std' => 1,
                'label' => __('Show product counts', 'dc-woocommerce-multi-vendor'),
            ),
			'hierarchical'       => array(
				'type'  => 'checkbox',
				'std'   => 1,
				'label' => __('Show hierarchy', 'dc-woocommerce-multi-vendor'),
			),
        );
        parent::__construct();
    }

    public function widget($args, $instance) {
        global $wp_query, $WCMp;
        
        $this->vendor_term_id = ( isset( $wp_query->queried_object->term_id ) ) ? $wp_query->queried_object->term_id : 0;
        $vendor = get_wcmp_vendor_by_term($this->vendor_term_id);
        if (!is_tax($WCMp->taxonomy->taxonomy_name) && !$vendor) {
            return;
        }
        $count = isset($instance['count']) ? $instance['count'] : $this->settings['count']['std'];
        $hierarchical = isset( $instance['hierarchical'] ) ? $instance['hierarchical'] : $this->settings['hierarchical']['std'];

        $this->widget_start($args, $instance); 
        $vendor_products = $vendor->get_products_ids();
        $product_ids = wp_list_pluck($vendor_products, 'ID');
        $associated_terms = array();
        foreach ($product_ids as $product_id) {
            $product_categories = get_the_terms($product_id, 'product_cat');
            if ($product_categories) {
                $term_ids = wp_list_pluck($product_categories, 'term_id');
                if ($term_ids) {
                    foreach ($term_ids as $term_id) {
                        $associated_terms[$term_id][] = $product_id;
                    }
                }
            }
        }
        $list_args = array('taxonomy' => 'product_cat');
        $product_cats = get_terms($list_args);
        if ($product_cats) {
            echo '<ul class="product-categories">';
            
            if($hierarchical) { 
                $product_cats = get_terms(array('taxonomy' => 'product_cat', 'include' => array_keys($associated_terms), 'hierarchical' =>true, 'hide_empty' => false));
                echo $this->get_hierarchical_categories($product_cats, $associated_terms, $count);
            } else {
				foreach ($product_cats as $product_cat) {
					$term_count = isset($associated_terms[$product_cat->term_id]) ? count(array_unique($associated_terms[$product_cat->term_id])) : 0;
					if ($term_count) {
						echo '<li class="cat-item cat-item-' . $product_cat->term_id . '"><a href="?category=' . $product_cat->slug . '">' . $product_cat->name . '</a>';
						if ($count) {
							echo '<span class="count">(' . $term_count . ')</span>';
						}
						echo '</li>';
					}
				}
			}
			
            echo '</ul>';
        }
        $this->widget_end($args);
    }
    
    function get_hierarchical_categories($terms, $associated_terms, $show_count, $parent_id = 0) {
    	$itemOutput = '';
        $has_parent_list = array();
    	foreach ($terms as $term_key => $term) {
            if(apply_filters('wcmp_widget_show_vpc_hierarchical_if_no_parents', true)){
                $term_count = isset($associated_terms[$term->term_id]) ? count(array_unique($associated_terms[$term->term_id])) : 0;
                if($term->parent != 0 ){ 
                    $parent_term = get_term( $term->parent, 'product_cat' );
                    if($parent_term){
                        if(in_array($parent_term->term_id, $has_parent_list)) continue;
                        $has_parent_list[] = $parent_term->term_id;
                        $term_count = isset($associated_terms[$term->term_id]) ? count(array_unique($associated_terms[$term->term_id])) : 0;
                        $parent_class = ' cat-parent';
                        $parent_count = 0;
                        $itemOutput .= '<li class="cat-item cat-item-' . $parent_term->term_id . $parent_class . '"><a href="?category=' . $parent_term->slug . '">' . $parent_term->name . '</a>';
                        if ($show_count) {
                                $itemOutput .= '<span class="count">(' . $parent_count . ')</span>';
                        }

                        $child_terms = get_terms(array('taxonomy' => 'product_cat', 'include' => array_keys($associated_terms), 'parent' => $parent_term->term_id, 'hierarchical' =>true, 'hide_empty' => false));
                        if($child_terms){
                            $parent_class = ' cat-parent';
                            $itemOutput .=  '<ul class="children">';
                            foreach ($child_terms as $c_term) {
                                $term_count = isset($associated_terms[$c_term->term_id]) ? count(array_unique($associated_terms[$c_term->term_id])) : 0;
                                $itemOutput .= '<li class="cat-item cat-item-' . $c_term->term_id . $parent_class . '"><a href="?category=' . $c_term->slug . '">' . $c_term->name . '</a>';
                                if ($show_count) {
                                        $itemOutput .= '<span class="count">(' . $term_count . ')</span>';
                                }
                            }
                            $itemOutput .=  '</ul>';
                        }

                        $itemOutput .= '</li>';
                    }
                }else{ 
                    if(in_array($term->term_id, $has_parent_list)) continue;
                    $has_parent_list[] = $term->term_id;
                    $itemOutput .= '<li class="cat-item cat-item-' . $term->term_id . '"><a href="?category=' . $term->slug . '">' . $term->name . '</a>';
                    if ($show_count) {
                            $itemOutput .= '<span class="count">(' . $term_count . ')</span>';
                    }
                    $child_terms = get_terms(array('taxonomy' => 'product_cat', 'include' => array_keys($associated_terms), 'parent' => $term->term_id, 'hierarchical' =>true, 'hide_empty' => false));
                    if($child_terms){
                        $parent_class = ' cat-parent';
                        $itemOutput .=  '<ul class="children">';
                        foreach ($child_terms as $c_term) {
                            $term_count = isset($associated_terms[$c_term->term_id]) ? count(array_unique($associated_terms[$c_term->term_id])) : 0;
                            $itemOutput .= '<li class="cat-item cat-item-' . $c_term->term_id . $parent_class . '"><a href="?category=' . $c_term->slug . '">' . $c_term->name . '</a>';
                            if ($show_count) {
                                    $itemOutput .= '<span class="count">(' . $term_count . ')</span>';
                            }
                        }
                        $itemOutput .=  '</ul>';
                    }
                    $itemOutput .= '</li>';
                }

            }else{
                if ($parent_id == $term->parent) { 
                    $output_inner = $this->get_hierarchical_categories($terms, $associated_terms, $show_count, $term->term_id);
                    $term_count = isset($associated_terms[$term->term_id]) ? count(array_unique($associated_terms[$term->term_id])) : 0;  
                    if ($term_count) {
                            if($output_inner != '') $parent_class = ' cat-parent';
                            else $parent_class = '';
                            $itemOutput .= '<li class="cat-item cat-item-' . $term->term_id . $parent_class . '"><a href="?category=' . $term->slug . '">' . $term->name . '</a>';
                            if ($show_count) {
                                    $itemOutput .= '<span class="count">(' . $term_count . ')</span>';
                            }
                            if($output_inner != '') {
                                    $itemOutput .=  '<ul class="children">' . $output_inner . '</ul>';
                                    $output_inner = '';
                            }
                            $itemOutput .= '</li>';
                    } else {

                    }
                }
            }
        }
		
        return $itemOutput;
    }
}
