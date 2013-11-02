<?php
    class Form extends Modules {

        public function __init() {
            //
        }
    
        static function __install() {
            $config = Config::current();
            $config->set("form_default_email", $config->email);
            $config->set("form_default_status", "drafts");
            $config->set("title_form", array("Title", "Body", "Email", "Name"));
            $config->set("type_form", array("text", "text_block", "text", "text"));
            $config->set("required_form", array("required", "required", "optional", "optional"));
            $config->set("attr_form", array("title", "body", "email", "name"));
            $config->set("form_allow_email", false);
            $config->set("form_allow_captcha", false);

            $enabled_array = "enabled_feathers";
            $feather = "formpost";
            $new = $config->$enabled_array;
            array_push($new, $feather);
            $config->set($enabled_array, $new);

            Group::add_permission("change_forms", "Change Forms");
        }
    
        static function __uninstall($confirm) {
            $config = Config::current();
            if ($confirm) {
                $config->remove("form_default_email");
                $config->remove("form_default_status");
                $config->remove("title_form");
                $config->remove("type_form");
                $config->remove("required_form");
                $config->remove("attr_form");
                $config->remove("form_allow_email");
                $config->remove("form_allow_captcha");
            }

            Group::remove_permission("change_forms");

            $enabled_array = "enabled_feathers";
            $feather = "formpost";
            $config->set($enabled_array,array_diff($config->$enabled_array, array($feather)));
        }

        static function admin_form_settings($admin) {
            if (!Visitor::current()->group->can("change_settings"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

            if (empty($_POST))
                return $admin->display("form_settings");

            if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
                show_403(__("Access Denied"), __("Invalid security key."));

            $config = Config::current();
            $set = array($config->set("form_default_email",$_POST['form_default_email']),
                         $config->set("form_default_status",$_POST['form_default_status']),
                         $config->set("title_form", explode(", ", $_POST['title_form'])),
                         $config->set("type_form", explode(", ", $_POST['type_form'])),
                         $config->set("required_form", explode(", ", $_POST['required_form'])),
                         $config->set("attr_form", explode(", ", $_POST['attr_form'])),
                         $config->set("form_allow_email", isset($_POST['form_allow_email'])),
                         $config->set("form_allow_captcha", isset($_POST['form_allow_captcha'])));

            if (!in_array(false, $set))
                Flash::notice(__("Settings updated."), "/admin/?action=form_settings");
        }


        static function settings_nav($navs) {
            if (Visitor::current()->group->can("change_forms"))
                $navs["form_settings"] = array("title" => __("Form", "form"));
            return $navs;
        }

        public function main_contact($main) {

        $config = Config::current();        
        $forms = Form::config_form();

        if (!empty($_POST)) {
            $post_array = array();
            $message_array = array();
            $i = 0;

            if ($config->form_allow_captcha and !check_captcha())
                    Flash::warning(__("Incorrect captcha code. Please try again."));

            foreach ($forms as $form) {
                if (empty($_POST[$form['attr']]) and ($form['required'] == "required"))
                    Flash::warning( $form['name']." can't be blank.");//error(__("Error"), __($form['name']." can't be blank."));

                if ($config->form_allow_email) {
                    $message_array[$i]['name'] = htmlspecialchars($form['name']);
                    $message_array[$i]['value'] = fix(htmlspecialchars($_POST[$form['attr']]));
                    $i++;
                }
                $post_array[$form['attr']] = fix(htmlspecialchars($_POST[$form['attr']]));
            }

            if ($config->form_allow_email and !Flash::exists("warning")) {
                $subject = _f("Тема:form add");
                $message = "You received form:\r\n";

                foreach ($message_array as $form) {
                    $message .= $form['name'];
                    $message .= ":";
                    $message .= $form['value'];
                    $message .= "\r\n";
                }    
                // print_r($message);die();
                $headers = "From:".$config->email."\r\n" .
                       "Reply-To:".$config->email. "\r\n" .
                       "X-Mailer: PHP/".phpversion();

                $sent = email($config->form_default_email, $subject, $message, $headers);
                if ($sent)
                    Flash::notice("Ваше  test письмо было отправлено.");
                else
                    Flash::warning("Ошибка при отправлении.");
            }

            if (!Flash::exists("warning")) {

                fallback($_POST['title'], sanitize($_POST['body']));

                Post::add($post_array,$_POST['title'],Post::check_url($_POST['title']),"formpost",Visitor::current()->id,"0",$config->form_default_status);
                Flash::notice("Form send.");
                } else {
                Flash::warning("Form don't send.");
            }
        }
        $main->display(array("pages/contact","pages/index"),array("forms" => $forms));            
        }

        static function config_form() {
            
            $config = Config::current();
            $form_array = array();
            $forms = array();

            $form_array['name'] = $config->title_form;
            $form_array['type'] = $config->type_form;
            $form_array['required'] = $config->required_form;
            $form_array['attr'] = $config->attr_form;
            
            $count = count($form_array['name']);
            for($i=0; $i < $count; $i++) {
                $forms[$i] = array('name' => $form_array['name'][$i],
                                   'type' => $form_array['type'][$i],
                               'required' => $form_array['required'][$i],
                                   'attr' => $form_array['attr'][$i]);
            }
            return $forms;
        }
    }
