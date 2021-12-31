<?php 

namespace Drupal\form_examples\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
* Provides an example block
*
*    @Block(
*       id = "form_examples_contact_form",
*       admin_label = @Translation("Form Examples: Contact Form"),
*       category = @Translation("Examples")
*   )
 */

 class ContactFormBlock extends BlockBase {
 
    /**
    * {@inheritdoc}
     */
    public function build(){
        $form = \Drupal::formBuilder()->getForm('\Drupal\form_examples\Form\ContactForm');
        return $form;
    }
 }