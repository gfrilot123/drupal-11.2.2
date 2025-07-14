<?php

/**
 * @file
 * A form to collect an email address for RSVP details.
 */

namespace Drupal\rsvplist\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class RSVPForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rsvplist_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //Attempt to get the fully loaded node object of the viewed page.
    $node = \Drupal::routeMatch()->getParameter('node');

    // Some pages my not be nodes though and $node will be NULL on those pages.
    // If a node was loaded, get the node id.
    if( !is_null($node) ) {
      $nid = $node->id();
    }
    else {
      // If a node could not be loaded, default to 0.
      $nid = 0;
    }

    // Establish the $form render array, it has an email text field
    // a submit button, and a hidden field containing the node ID.
    $form['email'] = [
      '#type' => 'textfield',
      '#title' => t('Email address'),
      '#size' => 25,
      '#description' => t('We will send email updates to the address you provide.'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('RSVP'),
    ];
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('email');

    // See EmailValidator.php and core.services.yml
    if( !(\Drupal::service('email.validator')->isValid($value))){
      $form_state->setErrorByName('email',
        $this->t('It appears that %mail is not a valid email. Please try
        again.', ['%mail' => $value]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
//    $submitted_email = $form_state->getValue('email');
//    $this->messenger()->addMessage(t("The form is working! You entered @entry.",
//    ['@entry' => $submitted_email]));
    try {
      // Begin Phase 1: initiate variables to save

      // Get current user ID.
      $uid = \Drupal::currentUser()->id();

      // Demonstration for how to load a full user object of the current user.
      // This $full_user var is not needed for this code,
      // but is shown for demonstration purposes.
      $full_user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());

      // Obtain values as entered into the Form.
      $nid = $form_state->getValue('nid');
      $email = $form_state->getValue('email');

      $current_time = \Drupal::time()->getRequestTime();
      // End Phase 1

      // Begin Phase 2: Save the value to the database

      // Start to build a query builder object $query.
      // https://www.drupal.org/docs/11/api/database-api/insert-queries

      $query = \Drupal::database()->insert('rsvplist');

      // Specify the fields that the query will insert into.
      $query->fields([
        'uid',
        'nid',
        'mail',
        'created',
      ]);

      // Set the values of the fields we selected.
      // Note that they must be in the same order as we defined them
      // in the $query->fields([...]) above.
      $query->values([
        $uid,
        $nid,
        $email,
        $current_time,
      ]);

      // Execute the query!
      // Drupal handles the next syntax of the query automatically!
      $query->execute();
      // End Phase 2

      // Begin Phase 3: Display a nice message

      // Provide the form submitter a nice message.
      \Drupal::messenger()->addMessage(
        t('Thank you for your RSVP, you are on the list for the event!')
      );
      // End Phase 3

    }
    catch (\Exception $e) {
      // Added an additional check for unique email entry
      if (strpos($e->getMessage(), 'unique')) {
        \Drupal::messenger()->addError(
          t('The email provided already exists, please try again.')
        );
      }
      else {
        \Drupal::messenger()->addError(
          t('Unable to save RSVP settings at this time due to database error.
        Please try again.')
        );
      }
    }
  }
}
