<?php

include_once "Mage/Customer/controllers/AccountController.php";

class Exinent_CustomerActivation_AccountController extends Mage_Customer_AccountController {

    public function createPostAction() {

        /** @var $session Mage_Customer_Model_Session */
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {

            $this->_redirect('*/*/');
            return;
        }
        $session->setEscapeMessages(true); // prevent XSS injection in user input
        if (!$this->getRequest()->isPost()) {

            $errUrl = $this->_getUrl('*/*/create', array('_secure' => true));
            $this->_redirectError($errUrl);
            return;
        }

        $customer = $this->_getCustomer();

        try {
            /* Extra Content Strart for add assign group to customer and wholesale */

            if ($this->getRequest()->getPost('group_id')) {
                $customer->setGroupId($this->getRequest()->getPost('group_id'));
            } else {
                $customer->getGroupId();
            }

            /* Extra Content End for add assign group to customer and wholesale */

            $errors = $this->_getCustomerErrors($customer);

            if (empty($errors)) {
                if ($this->getRequest()->getPost('group_id')) {
                    $customer->setGroupId($this->getRequest()->getPost('group_id'));
                } else {
                    $customer->getGroupId();
                }

                $customer->setUniqueloginId(Mage::helper('core')->getRandomString(9, '0123456789'));
                $customer->setUpsNumber($this->getRequest()->getPost('upsnumber'));
                $customer->setFedexNumber($this->getRequest()->getPost('fedexnumber'));
                $customer->setAccountspayableEmail($this->getRequest()->getPost('accountpayble_email'));
                $customer->setStoreType($this->getRequest()->getPost('storetype'));
                $customer->setWebsiteUrl($this->getRequest()->getPost('website_url'));
                $customer->setIswebsiteLive($this->getRequest()->getPost('iswebsitelive'));
                $customer->setDateFounded($this->getRequest()->getPost('datefounded'));
                $customer->setPaymentmethods($this->getRequest()->getPost('paymentterms'));
                $brandsCarry = $this->getRequest()->getPost('brandsyoucarry');
                $brands = implode(',', $brandsCarry);
                $customer->setBrandsCarry($brands);
                try {
                    $uploader = new Varien_File_Uploader('attachment');
                    $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png'));
                    $uploader->setAllowRenameFiles(true);
                    $path = Mage::getBaseDir('media') . DS . 'customerlogs' . DS;
                    $img = $uploader->save($path, $_FILES['attachment']['name']);
                    $logoImage = 'customerlogs/' . $_FILES['attachment']['name'];
                } catch (Exception $e) {
                    echo $e->getMessage();
                    Mage::log($e->getMessage());
                }
                $customer->setLogoImage($logoImage);
                try {
                    $paymentreference = new Varien_File_Uploader('net30reference');
                    $uploader->setAllowedExtensions(array('doc', 'pdf', 'txt', 'docx', 'jpg', 'jpeg', 'gif', 'png'));
                    $paymentreference->setAllowRenameFiles(true);
                    $paymentreferencepath = Mage::getBaseDir('media') . DS . 'net30Reference' . DS;
                    $imgpath = $paymentreference->save($paymentreferencepath, $_FILES['net30reference']['name']);
                    $paymentreferenceImage = 'net30Reference/' . $_FILES['net30reference']['name'];
                } catch (Exception $e) {
                    echo $e->getMessage();
                    Mage::log($e->getMessage());
                }
                $customer->setNet30Reference($paymentreferenceImage);
                $customer->save();
                $this->_dispatchRegisterSuccess($customer);
                $this->_successProcessRegistration($customer);
                return;
            } else {
                $this->_addSessionError($errors);
            }
        } catch (Mage_Core_Exception $e) {
            $variable = $this->getRequest()->getPost('customer_activation');
            $session->setCustomerFormData($this->getRequest()->getPost());
            if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
                $url = $this->_getUrl('customer/account/forgotpassword');
                if ($variable != 'customerActivation') {
                    $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
                }
                $session->setEscapeMessages(false);
            } else {
                $message = $e->getMessage();
            }
            if ($variable != 'customerActivation') {
                $session->addError($message);
            }
        } catch (Exception $e) {
            $session->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save the customer.'));
        }
        if ($variable == 'customerActivation') {
            $errUrl = $this->_getUrl('customerActivation/index/wholesale', array('_secure' => true));
            $emailError = 'emailerror';
            Mage::getSingleton('core/session')->setEmailError($emailError);
        } else {
            $errUrl = $this->_getUrl('*/*/create', array('_secure' => true));
        }

        $this->_redirectError($errUrl);
    }

    public function loginPostAction() {

        $groupid = $this->getRequest()->getParam('groupname');

        Mage::register('groupId', $groupid);
        if (!$this->_validateFormKey()) {
            $this->_redirect('*/*/');
            return;
        }

        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        $session = $this->_getSession();

        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');

            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $session->login($login['username'], $login['password']);
                    if ($session->getCustomer()->getIsJustConfirmed()) {
                        $this->_welcomeCustomer($session->getCustomer(), true);
                    }
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value = $this->_getHelper('customer')->getEmailConfirmationUrl($login['username']);
                            $message = $this->_getHelper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $session->addError($message);
                    $session->setUsername($login['username']);
                } catch (Exception $e) {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                }
            } else {
                $session->addError($this->__('Login and password are required.'));
            }
        }

        $this->_loginPostRedirect();
    }

}

?>
