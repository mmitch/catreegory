<?php # $Id$ 

// written by Christian Garbs <mitch@cgarbs.de> http://www.cgarbs.de

// Probe for a language include with constants. Still include defines later on, if some constants were missing
$probelang = dirname(__FILE__) . '/' . $serendipity['charset'] . 'lang_' . $serendipity['lang'] . '.inc.php';
if (file_exists($probelang)) {
    include $probelang;
}

include dirname(__FILE__) . '/lang_en.inc.php';

class serendipity_plugin_catreegory extends serendipity_plugin {
    var $title = PLUGIN_CATREEGORY_TITLE;

    function introspect(&$propbag) {
        global $serendipity;

        $propbag->add('name',        PLUGIN_CATREEGORY_NAME);
        $propbag->add('description', PLUGIN_CATREEGORY_DESC);
        $propbag->add('stackable',     true);
        $propbag->add('author',        'Christian Garbs');
        $propbag->add('version',       '0.1');
        $propbag->add('configuration', array('title', 'image', 'show_count'));
        $propbag->add('groups',        array('FRONTEND_VIEWS'));
    }

    function introspect_config_item($name, &$propbag)
    {
        global $serendipity;
        switch($name) {
            case 'title':
                $propbag->add('type',        'string');
                $propbag->add('name',        TITLE);
                $propbag->add('description', TITLE_FOR_NUGGET);
                $propbag->add('default',     PLUGIN_CATREEGORY_TITLE);
                break;

            case 'image':
                $propbag->add('type',         'string');
                $propbag->add('name',         XML_IMAGE_TO_DISPLAY);
                $propbag->add('description',  XML_IMAGE_TO_DISPLAY_DESC);
                $propbag->add('default',     serendipity_getTemplateFile('img/xml.gif'));
                break;
                                                                                            
            case 'show_count':
                $propbag->add('type',        'boolean');
                $propbag->add('name',        PLUGIN_CATREEGORY_SHOWCOUNT);
                $propbag->add('description', '');
                $propbag->add('default',     false);
                break;

            default:
                return false;
        }
        return true;
    }

    function find_by_id($categories, $id) {
        reset($categories);
        foreach ($categories as $key => $val) {
            if ($val['categoryid'] == $id) {
                return $key;
            }
        }
        return 0;
    }

    function generate_content(&$title) {
        global $serendipity;

        $title = $this->get_config('title');

        $categories = serendipity_fetchCategories('all', '', 'category_name ASC', 'read');

        $cat_count = array();
        /*
        if (serendipity_db_bool($this->get_config('show_count'))) {
            $cat_sql        = "SELECT c.categoryid, c.category_name, count(e.id) as postings
                                            FROM {$serendipity['dbPrefix']}entrycat ec,
                                                 {$serendipity['dbPrefix']}category c,
                                                 {$serendipity['dbPrefix']}entries e
                                            WHERE ec.categoryid = c.categoryid
                                              AND ec.entryid = e.id
                                              AND e.isdraft = 'false'
                                            GROUP BY c.categoryid, c.category_name
                                            ORDER BY postings DESC";
            $category_rows  = serendipity_db_query($cat_sql);
            if (is_array($category_rows)) {
                foreach($category_rows AS $cat) {
                    $cat_count[$cat['categoryid']] = $cat['postings'];
                }
            }

        }
        */

        $html       = '';

        $html .= '<ul id="serendipity_categories_list" style="list-style: none; margin: 0px; padding: 0px">';

        $image = $this->get_config('image', serendipity_getTemplateFile('img/xml.gif'));
        $image = (($image == "'none'" || $image == 'none') ? '' : $image);

        $use_parent  = $this->get_config('parent_base');
        $hide_parent = serendipity_db_bool($this->get_config('hide_parent'));
        $parentdepth = 0;

        $hide_parallel = serendipity_db_bool($this->get_config('hide_parallel'));
        $hidedepth     = 0;

        // check enabled subtrees
        $cat_active = array();
        if (isset($serendipity['GET']['category'])) {
            $cat_walk = $serendipity['GET']['category'];
            while ($cat_walk) {
                $cat_active[$cat_walk] = 1;
                $cat_walk = $categories[serendipity_plugin_catreegory::find_by_id($categories, $cat_walk)]['parentid'];
            }
        }

        if (is_array($categories) && count($categories)) {
            $categories = serendipity_walkRecursive($categories, 'categoryid', 'parentid', VIEWMODE_THREADED);
            foreach ($categories as $cid => $cat) {

                // only show top level categories or active subtrees
                if ($cat['depth'] > 0) {
                    if (!array_key_exists($cat['parentid'], $cat_active)) {
                        unset($categories[$cid]);
                        continue;
                    }
                }

                $categories[$cid]['feedCategoryURL'] = serendipity_feedCategoryURL($cat, 'serendipityHTTPPath');
                $categories[$cid]['categoryURL']     = serendipity_categoryURL($cat, 'serendipityHTTPPath');
                $categories[$cid]['paddingPx']       = $cat['depth']*6;
                $categories[$cid]['catdepth']        = $cat['depth'];

                if (!empty($cat_count[$cat['categoryid']])) {
                    $categories[$cid]['true_category_name'] = $cat['category_name'];
                    $categories[$cid]['category_name'] .= ' (' . $cat_count[$cat['categoryid']] . ')';
                }

                $html .= '<li class="category_depth' . $cat['depth'] . ' category_' . $cat['categoryid'] . '" style="display: block;">';

                if ( !empty($image) ) {
                    $html .= '<a class="serendipity_xml_icon" href="'. $categories[$cid]['feedCategoryURL'] .'"><img src="'. $image .'" alt="XML" style="border: 0px" /></a> ';
                }
                $html .= '<a href="'. $categories[$cid]['categoryURL'] .'" title="'. htmlspecialchars($cat['category_description']) .'" style="padding-left: '. $categories[$cid]['paddingPx'] .'px">'. htmlspecialchars($categories[$cid]['category_name']) .'</a>';
                $html .= '</li>' . "\n";
            }
        }

        $html .= '</ul>';

        $html .= sprintf(
            '<div class="category_link_all"><br /><a href="%s" title="%s">%s</a></div>',

            $serendipity['serendipityHTTPPath'] . $serendipity['indexFile'] . '?frontpage',
            ALL_CATEGORIES,
            ALL_CATEGORIES
        );

        echo $html;
    }
}
