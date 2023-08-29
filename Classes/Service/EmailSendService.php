<?php
namespace Cylancer\CyLending\Service;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 *
 * This file is part of the "cy_lending" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2023 C. Gogolin <service@cylancer.net>
 *
 * @package Cylancer\CyLending\Domain\Service
 *         
 */
class EmailSendService implements SingletonInterface
{

    /**
     *
     * @param array $recipient
     *            recipient of the email in the format array('recipient@domain.tld' => 'Recipient Name')
     * @param array $sender
     *            sender of the email in the format array('sender@domain.tld' => 'Sender Name')
     * @param array $replyTo
     *            if $replyTo is not empty then sender name will be the replyTo name, the sender email remains and the replyTo attribute is set.
     * @param string $subject
     *            subject of the email
     * @param string $templateName
     *            template name (UpperCamelCase)
     * @param string $extensionName
     *            is the name of the extension
     * @param array $variables
     *            variables to be passed to the Fluid view
     * @param array $attachments
     *            contains all attachment meta datas (file upload style):
     *            ['name' : Filename
     *            'type' : mine type
     *            'tmp_name' : file path
     *            'error' : exists an error
     *            'size' : file size ]
     * @return boolean TRUE on success, otherwise false
     */
    public function sendTemplateEmail(array $bccRecipients, array $sender, array $replyTo = [], String $subject, String $templateName, String $extensionName, array $variables = array(), $attachments = array())
    {

        /* @var ConfigurationManager $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);

        $extbaseFrameworkConfiguration = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $emailView */
        $emailView = GeneralUtility::makeInstance(StandaloneView::class);
        $emailView->getRequest()->setControllerExtensionName($extensionName);
        

        $viewDefinitions = $extbaseFrameworkConfiguration['plugin.']['tx_' . strtolower($extensionName) . '.']['view.'];

        $emailView->setTemplateRootPaths($viewDefinitions['templateRootPaths.']);
        $emailView->setLayoutRootPaths($viewDefinitions['layoutRootPaths.']);
        $emailView->setPartialRootPaths($viewDefinitions['partialRootPaths.']);

        $emailView->setTemplate($templateName);
        $emailView->assignMultiple($variables);

        // if you want to use german or other UTF-8 chars in subject enable next line
        $subject = $subject == null ? 'no subject' : $subject;
        $subject = '=?utf-8?B?' . base64_encode($subject) . '?=';

        /** @var $message \TYPO3\CMS\Core\Mail\MailMessage */
        $message = GeneralUtility::makeInstance(MailMessage::class);
        $message->setSubject($subject);
        foreach ($bccRecipients as $r) {
            $message->addBcc($r);
        }

        $message->setFrom($sender);
        if (!empty($replyTo)) {
            $replyAddress = array_key_first($replyTo);
            $message->setReplyTo($replyAddress, $replyTo[$replyAddress]);
        }

        foreach ($attachments as $attachment) {
            $message->attachFromPath($attachment['tmp_name'], $attachment['name'], $attachment['type']);
        }

        $emailBodyHtml = $emailView->render();
        $emailView->setFormat('txt');
        $emailBodyTxt = $emailView->render();
        // transform <a> to a simple url.
        // debug($emailBodyTxt);
        $emailBodyTxt = preg_replace('&<a.*href="(.+)".*/a>&', '$1', $emailBodyTxt);
        // debug($emailBodyTxt);
        $emailBodyTxt = strip_tags(htmlspecialchars_decode($emailBodyTxt));
        $emailBodyTxt = str_replace('&hellip;', '…', $emailBodyTxt);

        $message->text($emailBodyTxt);

        // transform new lines to div tags.
        // $emailBodyHtml = nl2br($emailBodyHtml);
        // $emailBodyHtml = str_replace("\n", '</div><div>', $emailBodyHtml);
        // $emailBodyHtml = preg_replace("&<div>\\s*</div>&", '<div><br/></div>', $emailBodyHtml);
        // debug($emailBodyHtml);
        // throw new \Exception();

        $message->html($emailBodyHtml);
        $message->send();
        // debug($emailBodyTxt);
        return $message->isSent();
    }
}
