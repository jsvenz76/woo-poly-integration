<?php

/**
 * This file is part of the hyyan/woo-poly-integration plugin.
 * (c) Hyyan Abo Fakher <tiribthea4hyyan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hyyan\WPI\Product;

use Hyyan\WPI\HooksInterface;

/**
 * Taxonomies
 *
 * Handle taxonomies translation
 *
 * @author Hyyan
 */
class Taxonomies
{

    /**
     * Construct object
     */
    public function __construct()
    {
        // manage taxonomies translation
        add_filter(
                'pll_get_taxonomies'
                , array($this, 'manageTaxonomiesTranslation')
        );

        // manage attributes label translation
        add_action(
                'init'
                , array($this, 'manageAttrLablesTranslation')
                , 11, 2
        );
        add_filter(
                'woocommerce_attribute_label'
                , array($this, 'translateAttrLable')
        );

        add_action('admin_print_scripts', array($this, 'addAttsTranslateButton'), 100);
        // extend meta list to include attributes
        add_filter(
                HooksInterface::PRODUCT_META_SYNC_FILTER
                , array($this, 'extendProductMetaList')
        );
    }

    /**
     * Notifty polylang about product taxonomies
     *
     * @param array $taxonomies array of cutoms taxonomies managed by polylang
     *
     * @return array
     */
    public function manageTaxonomiesTranslation($taxonomies)
    {
        $new = $this->getTaxonomies();
        $options = get_option('polylang');
        $taxs = $options['taxonomies'];
        $update = false;
        foreach ($new as $tax) {
            if (!in_array($tax, $taxs)) {
                $options['taxonomies'][] = $tax;
                $update = true;
            }
        }
        if ($update) {
            update_option('polylang', $options);
        }

        return array_merge($taxonomies, $new);
    }

    /**
     * Make all attributes lables managed by polylang string translation
     *
     * @global \Polylang $polylang
     * @global \WooCommerce $woocommerce
     *
     * @return boolean false if polylang or woocommerce can not be found
     */
    public function manageAttrLablesTranslation()
    {
        global $polylang, $woocommerce;

        if (!$polylang || !$woocommerce) {
            return false;
        }

        $attrs = wc_get_attribute_taxonomies();
        $section = 'Woocommerce Attributes';
        foreach ($attrs as $attr) {
            pll_register_string(
                    $attr->attribute_label
                    , $attr->attribute_label
                    , $section
            );
        }
    }

    /**
     * Translate the attribute label
     *
     * @param string $label original attribute label
     *
     * @return string translated attribute label
     */
    public function translateAttrLable($label)
    {
        return pll__($label);
    }

    /**
     * Extend the product meta list that must by synced
     *
     * @param array $metas current meta list
     *
     * @return array
     */
    public function extendProductMetaList(array $metas)
    {
        return array_merge($metas, array(
            '_product_attributes',
            '_default_attributes',
        ));
    }

    /**
     * Add a button before the attributes table to let the user know how to
     * translate the attributes labels
     *
     * @global type $pagenow
     *
     * @return boolean false if not attributes page
     */
    public function addAttsTranslateButton()
    {
        global $pagenow;
        if ($pagenow !== 'edit.php') {
            return false;
        }

        $isAttrPage = isset($_GET['page']) &&
                esc_attr($_GET['page']) === 'product_attributes';

        if (!$isAttrPage) {
            return false;
        }
        printf(
                '<script type="text/javascript">'
                . ' jQuery(document).ready(function ($) {'
                . '     $("<a href=\'%s\' class=\'button button-primary button-large\'>%s</a><br><br>")'
                . '         .insertBefore(".attributes-table");'
                . ' });'
                . '</script>'
                , admin_url('options-general.php?page=mlang&tab=strings&s&group=Woocommerce+Attributes&paged=1')
                , __('Translate Attributes Lables', 'woo-poly-integration')
        );
    }

    /**
     * Get taxonomies array
     *
     * @return array
     */
    protected function getTaxonomies()
    {
        return array_merge(wc_get_attribute_taxonomy_names(), array(
            'product_cat',
            'product_tag',
            'product_shipping_class'
        ));
    }

}
