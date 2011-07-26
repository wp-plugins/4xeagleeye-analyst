<?php
    /*
    * Plugin Name: EagleEye Analyst
    * Version: 1.4.1
    * Plugin URI: http://www.letsfx.com/wpanal
    * Description: Auto publish `Forex Analysis reports` posts on daily bases, to your blog. EagleEye is FOREX, market trading tool designed to cover daily trader`s needs. EagleEye is trader`s sharp eye on the FOREX market short term technical outlook, which, also, alert users with any changes on current market outlook. English, Russian and Arabic interfaces. Try this code on your posts to see full live report &lt;script type = &quot;text/javascript&quot; language = &quot;javascript&quot; src = &quot;http://www.letsfx.com/dailyreport/&quot; &gt;&lt;/script&gt;
    * Author: Aqlan
    * Author URI: http://blog.letsfx.com/
    */
    
    function my_fetch_url( $url, $method='GET', $body=array(), $headers=array() ) {
        $request = new WP_Http;
        $result = $request->request( $url , array( 'method'=>$method, 'body'=>$body, 'headers'=>$headers, 'user-agent'=>'LetsFX http://www.letsfx.com' ) );
        // Success?
        if ( !is_wp_error($result) && isset($result['body']) ) {
            return $result['body'];
            // Failure (server problem...)
        } else {
            return false;
        }
    }

    function EagleEye_Analyst_DailyReports() {
        $dow=date(w);              
        if($dow==0||$dow==6)return;
        if(!get_option('EagleEye_Analyst_last_DR')) add_option('Letsfx_last_DR', '');
        $dt=date('o.m.d');
        $LastDR = get_option('EagleEye_Analyst_last_DR');
        if($dt==$LastDR) return;        
        //set_time_limit(0);
        $active = get_option('EagleEye_Analyst_active');
        if(!$active) return;
        $cat = get_option('EagleEye_Analyst_cat');
        $eurusd = iif(get_option('EagleEye_Analyst_eurusd'),'EURUSD','');
        $gbpusd = iif(get_option('EagleEye_Analyst_gbpusd'),'GBPUSD','');
        $usdchf = iif(get_option('EagleEye_Analyst_usdchf'),'USDCHF','');
        $usdjpy = iif(get_option('EagleEye_Analyst_usdjpy'),'USDJPY','');

        $english = iif(get_option('EagleEye_Analyst_english'),'en','');
        $arabic = iif(get_option('EagleEye_Analyst_arabic'),'ar','');

        $instr_a = array($eurusd, $gbpusd, $usdchf, $usdjpy) ;
        $lang_a = array($english, $arabic) ;
                
        foreach($lang_a as $lang){
            if(strlen($lang)<2) continue;
            foreach($instr_a as $instr){
                if(strlen($instr)<2) continue;
                $body='<!--more--><script type="text/javascript" src="http://reports.4xeagleeye.com/?pair='.$instr.'&lang='.$lang.'&day='.date("o.m.d").'" ></script>';
                $excerp = my_fetch_url('http://reports.4xeagleeye.com/?target=html&domain=letsfx.com&display=excerp&pair='.$instr.'&lang='.$lang.'&day='.date("o.m.d"));
                if($excerp==false) continue;
                $excerp  = str_replace(array('\r\n','\r','\n','<!--EXCERP-->','<br />','<br>','<br/>'), array('','','','','','',''),$excerp);
                $body = '<p style="display:none;">'.$excerp.'</p>'.$body;
                $post = array(  
                'comment_status' => 'open',  
                'ping_status' => 'open', 
                'post_author' =>1, 
                'post_category' => array($cat),   
                'post_content' => $body, 
                'post_excerpt' =>  $excerp, 
                'post_status' => 'publish',  
                'post_title' =>  $instr.' Analysis '.$dt,    
                'post_type' => 'post'                    
                );            
                $hf= remove_filter('content_save_pre', 'wp_filter_post_kses');
                wp_insert_post( add_magic_quotes($post) );
                if($hf) add_filter('content_save_pre', 'wp_filter_post_kses');                  
            }
        }

        $LastDR=$dt;
        update_option('EagleEye_Analyst_last_DR',$LastDR);
    }

    register_activation_hook(__FILE__, 'EagleEye_Analyst_activation');
    register_deactivation_hook(__FILE__, 'EagleEye_Analyst_deactivation');
    add_action('EagleEye_Analyst_DR', 'EagleEye_Analyst_DailyReports');

    function EagleEye_Analyst_activation() {

        if(!get_option('EagleEye_Analyst_active')) add_option('EagleEye_Analyst_active', false);
        if(!get_option('EagleEye_Analyst_cat')) add_option('EagleEye_Analyst_cat', 0);
        if(!get_option('EagleEye_Analyst_eurusd')) add_option('EagleEye_Analyst_eurusd', false);
        if(!get_option('EagleEye_Analyst_gbpusd')) add_option('EagleEye_Analyst_gbpusd', false);
        if(!get_option('EagleEye_Analyst_usdchf')) add_option('EagleEye_Analyst_usdchf', false);
        if(!get_option('EagleEye_Analyst_usdjpy')) add_option('EagleEye_Analyst_usdjpy', false);        
        if(!get_option('EagleEye_Analyst_english')) add_option('EagleEye_Analyst_english', false);        
        if(!get_option('EagleEye_Analyst_arabic')) add_option('EagleEye_Analyst_arabic', false);        

    }

    function EagleEye_Analyst_deactivation() {
        wp_clear_scheduled_hook('EagleEye_Analyst_DR');
    }

    if (!class_exists("EagleEyeAnalyst")) {
        class EagleEyeAnalyst {
            function EagleEyeAnalyst() { //constructor
            }
            function add_admin_menu() {
                add_options_page('EagleEye Analyst', 'EagleEye Analyst', 'manage_options', 'EagleEye-Analyst-options', array(&$this, 'EagleEyeAnalystOptions'));
            } 

            function EagleEyeAnalystOptions(){
                if (isset($_POST['info_update'])) {
                    $active = $_POST['active'];  
                    update_option(EagleEye_Analyst_active,$active);
                    if($active){
                        wp_schedule_event(mktime(7,0,0,date('m'),date('d'),date('Y')), 'daily', 'EagleEye_Analyst_DR');
                        //update_option('EagleEye_Analyst_last_DR','');
                    }
                    else
                        wp_clear_scheduled_hook('EagleEye_Analyst_DR');
                    $cat = $_POST['cat'];  
                    update_option(EagleEye_Analyst_cat,$cat);
                    $english = $_POST['english'];  
                    update_option(EagleEye_Analyst_english,$english);
                    $arabic = $_POST['arabic'];  
                    update_option(EagleEye_Analyst_arabic,$arabic);
                    $eurusd = $_POST['eurusd'];  
                    update_option(EagleEye_Analyst_eurusd,$eurusd);
                    $gbpusd = $_POST['gbpusd'];  
                    update_option(EagleEye_Analyst_gbpusd,$gbpusd);
                    $usdchf = $_POST['usdchf'];  
                    update_option(EagleEye_Analyst_usdchf,$usdchf);
                    $usdjpy = $_POST['usdjpy'];  
                    update_option(EagleEye_Analyst_usdjpy,$usdjpy);                     
                }    

                $active = get_option('EagleEye_Analyst_active');
                $cat = get_option('EagleEye_Analyst_cat');
                $english = get_option('EagleEye_Analyst_english');
                $arabic = get_option('EagleEye_Analyst_arabic');
                $eurusd = get_option('EagleEye_Analyst_eurusd');
                $gbpusd = get_option('EagleEye_Analyst_gbpusd');
                $usdchf = get_option('EagleEye_Analyst_usdchf');
                $usdjpy = get_option('EagleEye_Analyst_usdjpy');
                $LastDR = get_option('EagleEye_Analyst_last_DR','Never');
                $NextDR = wp_next_scheduled('EagleEye_Analyst_DR');
                if($NextDR)
                    $NextDR = date('Y-m-d h:i A', $NextDR);
                if($active) $active_c='checked'; else $active_c='';

            ?> 
            <form target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float: right; background: #FFFFC0; padding: 5px; border: 1px dashed #FF9A35;">
                   <input type="hidden" name="cmd" value="_s-xclick">
                   <input type="hidden" name="hosted_button_id" value="AYDPUDCTRQUJL">
                   <table>
                      <tr><td align="center"><input type="hidden" name="on0" value="Donate">Donate</td></tr><tr><td align="center"><select name="os0">
                               <option value="Thanks">Thanks $5.00</option>
                               <option value="Thanks a lot">Thanks a lot $20.00</option>
                               <option value="Gratitude">Gratitude $50.00</option>
                         </select> </td></tr><tr><td align="center">
                            <input type="hidden" name="currency_code" value="USD">
                            <input type="image" src="http://image.ebdatube.com/images/paypalbutt.png" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                            <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
                         </td></tr>
                   </table>
                </form>
            <form action="options-general.php?page=EagleEye-Analyst-options" method="post">
                <h2>EagleEye Analyst</h2>
                <h3>Configuration</h3>
                
                <div style="border: 1px solid #FFFFFF ;width: 500px;padding:20px;margin:20px;">                     
                    <div class="updated fade" style="margin-bottom:20px;padding:10px">
                        Last run:&nbsp;<span style="color: Red;"> <?php echo $LastDR ?></span>&nbsp;&nbsp;&nbsp;Next run:&nbsp;<span style="color: Red;"><?php echo $NextDR ?></span> 
                    </div>
                    <p>Active: <input type="checkbox" name="active" <?php echo iif($active,'checked','') ?> ></p>
                    <p>Languages:<br/>
                        <input title="English" type="checkbox" name="english" <?php echo iif($english,'checked','') ?> />English
                        <input title="Arabic" type="checkbox" name="arabic" <?php echo iif($arabic,'checked','') ?> />Arabic
                    </p>
                    <p>Instruments:<br/>
                        <input title="EURUSD" type="checkbox" name="eurusd" <?php echo iif($eurusd,'checked','') ?> />EURUSD
                        <input title="GBPUSD" type="checkbox" disabled="disabled" name="gbpusd"  />GBPUSD
                        <input title="USDCHF" type="checkbox" disabled="disabled" name="usdchf"  />USDCHF
                        <input title="USDJPY" type="checkbox" disabled="disabled" name="usdjpy"  />USDJPY
                    </p>

                    <p>Category ID: <input type="text" name="cat" value="<?php echo $cat ?>" ></p>                    

                    <input class="button-primary" type="submit" name="info_update" value="Save Changes" />
                    <p align="center">Current server time:&nbsp; <?php echo date('Y-m-d h:i A') ?> </p>
                </div>
                <h3>Preview sample</h3> 
                <div style="border: 1px solid #FFFFFF ;width: 700px;padding:20px;margin:20px;">           
                    <script type="text/javascript" language="javascript" src="http://www.letsfx.com/dailyreport/"></script>
                </div>
            </form> 

            <?php                
                 
            }                                                                     
        }
    } 

    if (class_exists("EagleEyeAnalyst")) {
        if (!isset($i_EagleEyeAnalyst)) $i_EagleEyeAnalyst = new EagleEyeAnalyst();
    }

    if (isset($i_EagleEyeAnalyst)) {
        //Actions
        add_action('admin_menu', array($i_EagleEyeAnalyst, 'add_admin_menu'));
        //add_action('wp_head', 'EagleEyeAnalyst_css'); 
        //Filters
    }
    unset($i_EagleEyeAnalyst);


    function EagleEyeAnalyst_css() {
        $x = ( 'rtl' == get_bloginfo( 'text_direction' ) ) ? 'right' : 'left';

        echo "
        ";
    }
    function iif($b, $t, $f){
        if($b) return $t;
        return $f;
    } 

 /*   function my_excerpts($content = false) {
        // If is the home page, an archive, or search results
        //if(is_front_page() || is_archive() || is_search()) :
        $content = strip_shortcodes($content);
        $content = str_replace(']]>', ']]>', $content);
        $content = strip_tags($content);

        $excerpt_length = 20;
        $words = explode(' ', $content, $excerpt_length + 1);
        if(count($words) > $excerpt_length) :
        array_pop($words);
        array_push($words, '…');
        $content = implode(' ', $words);
        endif;
        $content = '<p>' . $content . '</p>';

        //endif;

        // Make sure to return the content

        return 'fff'.$content;
    }
    add_filter('bp_create_excerpt', 'my_excerpts');

    function my_bp_blogs_record_activity_content( $bp_excerpt, $content ) {
        return the_excerpt();
    }
    add_filter( 'bp_blogs_record_activity_content', 'my_bp_blogs_record_activity_content', 1, 2 );
    
function my_bp_post_excerpt($activity_content, $post, $permalink) {
    //return the_excerpt();
    if($post->post_excerpt)           
        return $post->post_excerpt;
    else
        return $activity_content;
}
add_filter( 'bp_get_activity_content_body', 'my_bp_post_excerpt', 1, 3 ); */
                       
?>
