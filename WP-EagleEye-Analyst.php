<?php
    /*
    * Plugin Name: EagleEye Analyst
    * Version: 1.1.6
    * Plugin URI: http://www.letsfx.com/business/37-technical-analysis-generator/59-analysis-generator.html
    * Description: Auto publish 4 `Forex Analysis reports` posts on daily bases, to your blog. EagleEye is FOREX, market trading tool designed to cover daily trader`s needs. EagleEye is trader`s sharp eye on the FOREX market short term technical outlook, which, also, alert users with any changes on current market outlook. English, Russian and Arabic interfaces. Try this code on your posts to see full live report &lt;script type = &quot;text/javascript&quot; language = &quot;javascript&quot; src = &quot;http://www.letsfx.com/dailyreport/&quot; &gt;&lt;/script&gt;
    * Author: Aqlan
    * Author URI: http://www.letsfx.com/
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
        $dt=date('Y-m-d');
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
                $body=my_fetch_url('http://reports.4xeagleeye.com/eagleeye.php?target=html&pair='.$instr.'&lang='.$lang.'&ref='.$_SERVER['SERVER_NAME']);
                if($body==false) continue;
                $pos1 = mb_stripos($body, '<!--EXCERP-->',0,'UTF-8');
                $pos1 = mb_stripos($body, '>', $pos1 + 1 ,'UTF-8');
                $pos2 = mb_stripos($body, '<!--EXCERP-->',$pos1,'UTF-8');
                $excerp = mb_substr($body, $pos1 + 1, $pos2 - $pos1 - 1,'UTF-8');
                
                $pos1 = mb_stripos($body, '<body',0,'UTF-8');
                $pos1 = mb_stripos($body, '>', $pos1 + 1 ,'UTF-8');
                $pos2 = mb_stripos($body, '</body>',0,'UTF-8');
                $body = mb_substr($body, $pos1 + 1, $pos2 - $pos1 - 1,'UTF-8');
                                
                //$body='<textarea cols="85" rows="20">'.$body.'</textarea>' ;
                while(mb_stripos($body, "  ",0,'UTF-8')!==false)
                    $body  = str_replace("  ", " ",$body);
                $body  = str_replace(array('\r\n','\r','\n'), array('','',''),$body);
                $post = array(  
                'comment_status' => 'closed',  
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
        delete_option('EagleEye_Analyst_last_DR');
        delete_option('EagleEye_Analyst_active');
        delete_option('EagleEye_Analyst_cat');
        delete_option('EagleEye_Analyst_english');
        delete_option('EagleEye_Analyst_arabic');
        delete_option('EagleEye_Analyst_eurusd');
        delete_option('EagleEye_Analyst_gbpusd');
        delete_option('EagleEye_Analyst_usdchf');
        delete_option('EagleEye_Analyst_usdjpy');
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
                    if($active)
                        wp_schedule_event(mktime(7,0,0,date('m'),date('d'),date('Y')), 'daily', 'EagleEye_Analyst_DR');
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
                        <input title="GBPUSD" type="checkbox" name="gbpusd" <?php echo iif($gbpusd,'checked','') ?> />GBPUSD
                        <input title="USDCHF" type="checkbox" name="usdchf" <?php echo iif($usdchf,'checked','') ?> />USDCHF
                        <input title="USDJPY" type="checkbox" name="usdjpy" <?php echo iif($usdjpy,'checked','') ?> />USDJPY
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
        <style type='text/css'>
        div#LetsFX p {margin:0px;line-height: normal;}
        div#LetsFX h3{margin:5px;line-height: normal;} 
        div#LetsFX h2{margin:5px;line-height: normal;color:white;}
        div#LetsFX tr {background-color: white;padding:0px}
        div#LetsFX tr td {border:0px;line-height: normal;padding:0px;vertical-align:middle;}
        div#LetsFX table td h2.header {line-height: normal;color:white;margin:0px;text-shadow:#E0E0E0 0 1px 1px}
        div#LetsFX table {border-collapse: separate;border-color: #585858;font-size:11px}
        div#LetsFX table.ee_main,div#LetsFX {border:0px;border-spacing: 1px 1px;color:#585858;margin: 0px;background-color: transparent;font-size: 11px;font-family: Arial, Verdana, Tahoma, Helvetica;}
        div#LetsFX table.eemain td{border:0px;margin: 5px;}
        div#LetsFX table.list {border:0px;border-spacing: 1px 1px;}
        div#LetsFX table.list td{border:0px;}
        div#LetsFX table.dailyprofile {border:0px;border-spacing: 5px 2px;} 
        div#LetsFX table.supres {border:0px;border-spacing: 15px 5px;} 
        div#LetsFX table.supres td{text-align:center;border-bottom-width: 1px;border-bottom-style:dotted;border-bottom-color:#C0C0C0;}  
        div#LetsFX table.summary td{text-align:center;border-bottom-width: 1px;border-bottom-style:dotted;border-bottom-color:#C0C0C0;}
        div#LetsFX table.alerts td{border:0px;margin: 5px;border-bottom-width: 1px;border-bottom-style:dotted;border-bottom-color:#C0C0C0;}
        div#LetsFX tr.row  {border:0px;border-top:1px solid white;}
        div#LetsFX tr.altrow  {border:0px;background-color: #F9F9F9;}
        div#LetsFX .header {color:white;background-color: #585858;color:#F0F0F0;height:40px;vertical-align: middle;border:0px;border-bottom: 1px solid #E3E3E3;}
        div#LetsFX tr td.caption {border-width:0px;font-weight: bold;background-color: #F8F8F8;color:#585858;border-bottom: 1px solid #E3E3E3;}
        div#LetsFX img{border:0px;}
        </style>
        ";
    }
    function iif($b, $t, $f){
        if($b) return $t;
        return $f;
    }                        
?>
