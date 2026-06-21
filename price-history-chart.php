<?php
/*
Plugin Name: Woo Price History Chart
Description: نمایش نمودار تاریخچه قیمت محصولات ووکامرس
Version: 1.8
*/

if (!defined('ABSPATH')) exit;

class WooPriceHistoryChart {

    const MAX_RECORDS = 150;

    public function __construct() {

        add_action('wp_enqueue_scripts', [$this,'scripts']);
        add_shortcode('price_history_chart', [$this,'chart_shortcode']);
        add_action('woocommerce_admin_process_product_object', [$this,'detect_price_change'],10,1);
        add_action('admin_menu', [$this,'admin_menu']);
    }

    /* ---------------------------------------------------
       Load Scripts
    --------------------------------------------------- */

    public function scripts(){

        wp_register_script(
            'chartjs',
            plugin_dir_url(__FILE__) . 'assets/js/chart.js',
            [],
            null,
            true
        );

        wp_register_script(
            'price-history-init',
            plugin_dir_url(__FILE__) . 'assets/js/chart-init.js',
            ['chartjs'],
            null,
            true
        );

        wp_register_style(
            'price-history-style',
            plugin_dir_url(__FILE__) . 'assets/css/style.css',
            [],
            '1.0'
        );
    }

    /* ---------------------------------------------------
       Limit History
    --------------------------------------------------- */

    private function limit_history($history){

        if(count($history) > self::MAX_RECORDS){
            $history = array_slice($history, -self::MAX_RECORDS);
        }

        return $history;
    }

    /* ---------------------------------------------------
       Detect Price Change
    --------------------------------------------------- */

    public function detect_price_change($product){

        $product_id = $product->get_id();
        $price = $product->get_price();

        if(!$price) return;

        $history = get_post_meta($product_id,'_price_history',true);
        if(!$history) $history=[];

        $last = end($history);

        if(!$last || $last['price'] != $price){

            $history[]=[
                'price'=>floatval($price),
                'date'=>current_time('Y-m-d')
            ];

            $history = $this->limit_history($history);

            update_post_meta($product_id,'_price_history',$history);
        }
    }

    /* ---------------------------------------------------
       Persian Date
    --------------------------------------------------- */

    private function fa_number($number){

        $english = ['0','1','2','3','4','5','6','7','8','9'];
        $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];

        return str_replace($english,$persian,$number);
    }

    private function persian_date($date){

        $timestamp = strtotime($date);
        $date = wp_date('Y/m/d',$timestamp);

        return $this->fa_number($date);
    }

    /* ---------------------------------------------------
       Shortcode
    --------------------------------------------------- */

    public function chart_shortcode(){

        global $product;
        if(!$product) return;

        $history = get_post_meta($product->get_id(), '_price_history', true);
        if(!$history) return "داده‌ای وجود ندارد";

        $dates=[];
        $prices=[];
        usort($history, function($a,$b){
            return strtotime($a['date']) - strtotime($b['date']);
        });
        
        foreach($history as $row){
            $dates[]  = $this->persian_date($row['date']);
            $prices[] = floatval($row['price']);
        }

        wp_enqueue_script('chartjs');
        wp_enqueue_script('price-history-init');
        wp_enqueue_style('price-history-style');

        /* ---- Style Options ---- */

        $hex = get_option('ph_chart_bg_color','#2271b1');
        $opacity = get_option('ph_chart_bg_opacity',0.15);

        list($r,$g,$b) = sscanf($hex,"#%02x%02x%02x");
        $rgba = "rgba($r,$g,$b,$opacity)";

        $chart_style = [
            'theme'     => get_option('ph_chart_theme','default'),
            'lineColor' => get_option('ph_chart_line_color','#2271b1'),
            'bgColor'   => $rgba,
            'gridColor' => get_option('ph_chart_grid_color','#e5e5e5'),
            'lineWidth' => get_option('ph_chart_line_width',3),
        ];

        ob_start();
        ?>

        <div class="price-history-chart-wrapper">
            <div class="price-history-chart-inner">
                <canvas id="priceHistoryChart"></canvas>
            </div>
        </div>

        <script>
        window.priceHistoryData = {
            labels: <?php echo json_encode($dates); ?>,
            prices: <?php echo json_encode($prices); ?>
        };
        window.priceHistoryStyle = <?php echo json_encode($chart_style); ?>;
        </script>

        <?php
        return ob_get_clean();
    }

    /* ---------------------------------------------------
       Admin Menu
    --------------------------------------------------- */

    public function admin_menu(){

        add_menu_page(
            "Price History",
            "Price History",
            "manage_options",
            "price-history",
            [$this,'admin_page']
        );
    }

    /* ---------------------------------------------------
       Admin Page
    --------------------------------------------------- */

    public function admin_page(){

        /* Save Style */

        if(isset($_POST['save_chart_style'])){

            update_option('ph_chart_theme', sanitize_text_field($_POST['chart_theme']));
            update_option('ph_chart_line_color', sanitize_hex_color($_POST['line_color']));
            update_option('ph_chart_bg_color', sanitize_hex_color($_POST['bg_color']));
            update_option('ph_chart_grid_color', sanitize_hex_color($_POST['grid_color']));
            update_option('ph_chart_line_width', intval($_POST['line_width']));
            update_option('ph_chart_bg_opacity', floatval($_POST['bg_opacity']));

            echo "<div class='updated'><p>تنظیمات نمودار ذخیره شد</p></div>";
        }

        /* Manual Add */

        if(isset($_POST['manual_add'])){

            $product_id = intval($_POST['product_id']);
            $price = floatval($_POST['manual_price']);
            $date = sanitize_text_field($_POST['manual_date']);

            $history = get_post_meta($product_id,'_price_history',true);
            if(!$history) $history=[];

            $history[]=[
                'price'=>$price,
                'date'=>$date
            ];

            update_post_meta($product_id,'_price_history',$history);

            echo "<div class='updated'><p>رکورد اضافه شد</p></div>";
        }

        /* Delete Product History */

        if(isset($_POST['delete_product_history'])){
            $pid = intval($_POST['delete_product_id']);
            delete_post_meta($pid,'_price_history');
            echo "<div class='updated'><p>تاریخچه محصول حذف شد</p></div>";
        }

        /* Delete All */

        if(isset($_POST['delete_all_history'])){
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key='_price_history'");
            echo "<div class='updated'><p>کل تاریخچه‌ها حذف شد</p></div>";
        }

        $chart_theme = get_option('ph_chart_theme','default');
        $line_color  = get_option('ph_chart_line_color','#2271b1');
        $bg_color    = get_option('ph_chart_bg_color','#2271b1');
        $grid_color  = get_option('ph_chart_grid_color','#e5e5e5');
        $line_width  = get_option('ph_chart_line_width',3);
        $bg_opacity  = get_option('ph_chart_bg_opacity',0.15);
        ?>

        <div class="wrap">
        <h1>تنظیمات Price History</h1>

        <h2>استایل نمودار</h2>
        <form method="post">

        تم:
        <select name="chart_theme">
            <option value="default" <?php selected($chart_theme,'default'); ?>>پیشفرض</option>
            <option value="dark" <?php selected($chart_theme,'dark'); ?>>دارک</option>
            <option value="minimal" <?php selected($chart_theme,'minimal'); ?>>مینیمال</option>
            <option value="custom" <?php selected($chart_theme,'custom'); ?>>سفارشی</option>
        </select><br><br>

        رنگ خط:
        <input type="color" name="line_color" value="<?php echo $line_color; ?>"><br><br>

        رنگ پس زمینه:
        <input type="color" name="bg_color" value="<?php echo $bg_color; ?>"><br><br>

        شفافیت:
        <input type="range" name="bg_opacity" min="0" max="1" step="0.05"
        value="<?php echo $bg_opacity; ?>"
        oninput="this.nextElementSibling.value = this.value">
        <output><?php echo $bg_opacity; ?></output><br><br>

        رنگ گرید:
        <input type="color" name="grid_color" value="<?php echo $grid_color; ?>"><br><br>

        ضخامت خط:
        <input type="number" name="line_width" value="<?php echo $line_width; ?>" min="1" max="10"><br><br>

        <button name="save_chart_style" class="button button-primary">ذخیره</button>
        </form>

        <hr>

        <h2>افزودن دستی رکورد</h2>
        <form method="post">
            شناسه محصول: <input type="number" name="product_id" required><br><br>
            قیمت: <input type="number" name="manual_price" required><br><br>
            تاریخ (YYYY-MM-DD): <input type="date" name="manual_date" required><br><br>
            <button name="manual_add" class="button">افزودن</button>
        </form>

        <hr>

        <h2>حذف تاریخچه یک محصول</h2>
        <form method="post">
            شناسه محصول: <input type="number" name="delete_product_id" required>
            <button name="delete_product_history" class="button">حذف</button>
        </form>

        <hr>

        <h2>حذف کل تاریخچه‌ها</h2>
        <form method="post">
            <button name="delete_all_history" class="button button-secondary">
            حذف همه
            </button>
        </form>

        </div>

        <?php
    }

}

new WooPriceHistoryChart();
