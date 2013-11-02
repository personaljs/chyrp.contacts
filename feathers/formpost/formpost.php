<?php
    class Formpost extends Feathers implements Feather {
        public function __init() {

          $config = Config::current();
          $forms = Form::config_form();

          foreach ($forms as $form) {
            $this->setField(array("attr" => $form['attr'],
                                  "type" => $form['type'],
                                 "label" => __($form['name'], "formpost"),
                       $form['required'] => true));
          }
              }
         public function submit() {
          $config = Config::current();
          $forms = Form::config_form();

          $post_array = array();
          $message_array = array();
          $i = 0;

          foreach ($forms as $form) {
           if (empty($_POST[$form['attr']]) and ($form['required'] == "required")){
              error(__("Error"), __($form['name']." can't be blank."));
            } else {
                if ($config->form_allow_email) {
                  $message_array[$i]['name'] = htmlspecialchars($form['name']);
                  $message_array[$i]['value'] = fix(htmlspecialchars($_POST[$form['attr']]));
              $i++;
                }
              }
              $post_array[$form['attr']] = fix(htmlspecialchars($_POST[$form['attr']]));
            }
          

          if ($config->form_allow_email) {
              $subject = _f("Тема: form add");
              $message = "You received form:\r\n";
              foreach ($message_array as $form) {
                $message .= $form['name'];
                $message .= ":";
                $message .= $form['value'];
                $message .= "\r\n";
              }    
              //  print_r($message);die();
              $headers = "From:".$config->email."\r\n" .
                     "Reply-To:".$config->email. "\r\n" .
                     "X-Mailer: PHP/".phpversion();

              $sent = email($config->form_default_email, $subject, $message, $headers);
           if ($sent)
            Flash::notice("Ваше письмо было отправлено.");
           else
            Flash::notice("Ошибка при отправлении.");
         }

          fallback($_POST['slug'], sanitize($_POST['title']));

            return Post::add($post_array,$_POST['slug'],Post::check_url($_POST['slug']),"formpost",Visitor::current()->id,"0",$config->form_default_status);
        }


       
        public function update($post) {
          $config = Config::current();
          $forms = Form::config_form();
          $post_array = array();
          foreach ($forms as $form) {
           if (empty($_POST[$form['attr']]) and ($form['required'] == "required")){
              error(__("Error"), __($form['name']." can't be blank."));
            } else {
              $post_array[$form['attr']] = htmlspecialchars($_POST[$form['attr']]);
            }
          }
          fallback($_POST['slug'], sanitize($_POST['title']));

          return $post->update($post_array,$_POST['slug'],Post::check_url($_POST['slug']),"formpost",Visitor::current()->id,"0",$config->form_default_status);
        }

        public function title($post) {
            return oneof($post->title, $post->title_from_excerpt());
        }

        public function excerpt($post) {
            return $post->body;
        }

        public function feed_content($post) {
            return $post->body;
        }
    }
