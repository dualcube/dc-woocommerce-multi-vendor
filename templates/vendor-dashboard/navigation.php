<?php
/*
 * The template for displaying vendor dashboard nav
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/navigation.php
 *
 * @author     WC Marketplace
 * @package     WCMp/Templates
 * @version   2.4.5
 */
if (!defined('ABSPATH')) {
    exit;
}
global $WCMp;

sksort($nav_items, 'position', true);

$add_vendor_navigation = is_user_wcmp_vendor(get_current_user_id());
if(!$add_vendor_navigation) $add_vendor_navigation = is_user_wcmp_pending_vendor(get_current_user_id());
if(!$add_vendor_navigation) $add_vendor_navigation = is_user_wcmp_rejected_vendor(get_current_user_id());
if(!$add_vendor_navigation){
    return;
}

do_action('wcmp_before_vendor_dashboard_navigation');
?>
<!-- Navigation -->
<nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
    <div class="navbar-header">
        <button data-toggle="collapse-side" data-target="#side-collapse" type="button" class="navbar-toggle pull-left larr collapsed">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
    </div>
    <!-- /.navbar-header -->
    <div class="navbar-default sidebar side-collapse" id="side-collapse" role="navigation">
        <div class="mCustomScrollbar" data-mcs-theme="minimal-dark">
            <div class="sidebar-nav navbar-collapse">
                <ul class="nav" id="side-menu">
                    <?php foreach ($nav_items as $key => $item): ?>
                        <?php if (current_user_can($item['capability']) || $item['capability'] === true): ?>
                            <li class="nav-item <?php if(!empty($item['submenu'])){ echo 'hasmenu';} ?>">
                                <?php if(array_key_exists($WCMp->endpoints->get_current_endpoint(), $item['submenu'])){ $force_active = true;} else {$force_active = false;}?>
                                <a href="<?php echo esc_url($item['url']); ?>" target="<?php echo $item['link_target'] ?>" data-menu_item="<?php echo $key ?>" class="<?php echo implode(' ', array_map('sanitize_html_class', wcmp_get_vendor_dashboard_nav_item_css_class($key, $force_active))); ?>">
                                    <i class="<?php echo $item['nav_icon'] ?>"></i> 
                                    <span><?php echo esc_html($item['label']); ?></span>
                                    <?php if(!empty($item['submenu'])): ?><i class="wcmp-font ico-downarrow-2-icon"></i><?php endif; ?>
                                </a>
                                <?php if (!empty($item['submenu']) && is_array($item['submenu'])): sksort($item['submenu'], 'position', true) ?>
                                    <ul class="nav submenu" <?php if(!in_array('active', wcmp_get_vendor_dashboard_nav_item_css_class($key, $force_active))){ echo 'style="display:none"'; }else{ echo 'style="display:block"'; } ?>>
                                        <?php foreach ($item['submenu'] as $submenukey => $submenu): ?>
                                            <?php if(current_user_can($submenu['capability']) || $submenu['capability'] === true): ?>
                                                <li>
                                                    <a href="<?php echo esc_url($submenu['url']); ?>" target="<?php echo $submenu['link_target'] ?>" class="<?php echo implode(' ', array_map('sanitize_html_class', wcmp_get_vendor_dashboard_nav_item_css_class($submenukey))); ?>">-- <?php echo esc_html($submenu['label']); ?></a>
                                                </li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <!-- /.sidebar-collapse -->
    </div>
    <!-- /.navbar-static-side -->
</nav>
<?php do_action('wcmp_after_vendor_dashboard_navigation'); ?>