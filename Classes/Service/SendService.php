<?php
namespace Cylancer\CyLending\Service;

use Cylancer\CyLending\Controller\LendingController;
use Cylancer\CyLending\Domain\Model\Lending;
use Cylancer\CyLending\Domain\Model\LendingObject;
use Cylancer\CyLending\Domain\Model\ValidationResults;
use Cylancer\CyLending\Domain\Repository\FrontendUserGroupRepository;
use Cylancer\CyLending\Domain\Repository\FrontendUserRepository;
use Cylancer\CyLending\Domain\Repository\LendingRepository;
use Cylancer\CyLending\Domain\Model\FrontendUser;
use Cylancer\CyLending\Domain\Model\FrontendUserGroup;

use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 *
 * This file is part of the "lending" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2025 C. Gogolin <service@cylancer.net>
 *
 */

class SendService implements SingletonInterface
{

    private const EXTENSION_NAME = 'cy_lending';

    private const APPROVER_MAIL_TEMPLATE = 'ApproverMessageMail';
    private const AVAILABILITY_REQUEST_RESULT_MAIL_TEMPLATE = 'AvailabilityRequestResultMail';
    private const INFORM_PREVIOUS_BORROWER_MAIL_TEMPLATE = 'InformPreviousBorrowerMail';
    private const OBSERVER_MAIL_TEMPLATE = 'ObserverMessageMail';
    private const INFORM_CANCEL_LENDING_MAIL_TEMPLATE = 'InformCancelLendingMail';
    private const INFORM_CANCEL_AVAILABILITY_REQUEST_MAIL_TEMPLATE = 'InformCancelAvailabilityRequestMail';


    public function __construct(
        private readonly FrontendUserGroupRepository $frontendUserGroupRepository,
        private readonly FrontendUserRepository $frontendUserRepository,
        private readonly FrontendUserService $frontendUserService,
    ) {
    }

    /**
     * @param Lending $myAvailabilityRequest
     * @param int[] $frontendUserStorageUids
     * @param RequestInterface $request
     * @param string $language
     * @return void
     */
    public function informAboutCancelAvailabilityRequest(Lending $myAvailabilityRequest, array $frontendUserStorageUids, RequestInterface $request, string $language): void
    {
        /** @var FrontendUserGroup $g */
        $observers = $myAvailabilityRequest->getObject()->getObserverGroup();
        $approvers = $myAvailabilityRequest->getObject()->getApproverGroup();

        $groups =
            array_merge(
                $this->frontendUserService->getAllGroups($observers),
                $this->frontendUserService->getAllGroups($approvers)
            );
        if (!empty($groups)) {
            /** @var FrontendUser $receiver */
            foreach ($this->frontendUserRepository->getUsersOfGroups($groups, $frontendUserStorageUids) as $receiver) {
                if (!empty($receiver->getEmail())) {
                    $fluidEmail = GeneralUtility::makeInstance(FluidEmail::class);
                    $fluidEmail
                        ->setRequest($request)
                        ->to(new Address($receiver->getEmail(), $receiver->getFirstName() . ' ' . $receiver->getLastName()))
                        ->from(new Address(
                            MailUtility::getSystemFromAddress(),
                            LocalizationUtility::translate('message.cancelAvailabilityRequest.email.senderName', SendService::EXTENSION_NAME)
                        ))
                        ->subject(LocalizationUtility::translate('message.cancelAvailabilityRequest.email.subject', SendService::EXTENSION_NAME))
                        ->format(FluidEmail::FORMAT_BOTH) // send HTML and plaintext mail
                        ->setTemplate(SendService::INFORM_CANCEL_AVAILABILITY_REQUEST_MAIL_TEMPLATE)
                        ->assign('availabilityRequest', $myAvailabilityRequest)
                        ->assign('language', $language)
                        ->assign('user', $receiver)
                    ;
                    GeneralUtility::makeInstance(MailerInterface::class)->send($fluidEmail);
                }
            }
        }
    }

    /**
     * @param Lending $myLending
     * @param int[] $frontendUserStorageUids
     * @param RequestInterface $request
     * @param string $language
     * @return void
     */
    public function informAboutCanceling(
        Lending $myLending,
        array $frontendUserStorageUids,
        RequestInterface $request,
        string $language
    ): void {

        /** @var FrontendUserGroup $g */
        $observers = $myLending->getObject()->getObserverGroup();
        $approvers = $myLending->getObject()->getApproverGroup();

        $groups =
            array_merge(
                $this->frontendUserService->getAllGroups($observers),
                $this->frontendUserService->getAllGroups($approvers)
            );
        if (!empty($groups)) {
            /** @var FrontendUser $receiver */
            foreach ($this->frontendUserRepository->getUsersOfGroups($groups, $frontendUserStorageUids) as $receiver) {
                if (!empty($receiver->getEmail())) {
                    $fluidEmail = GeneralUtility::makeInstance(FluidEmail::class);
                    $fluidEmail
                        ->setRequest($request)
                        ->to(new Address($receiver->getEmail(), $receiver->getFirstName() . ' ' . $receiver->getLastName()))
                        ->from(new Address(
                            MailUtility::getSystemFromAddress(),
                            LocalizationUtility::translate('message.cancel.email.senderName', SendService::EXTENSION_NAME)
                        ))
                        ->subject(LocalizationUtility::translate('message.cancel.email.subject', SendService::EXTENSION_NAME))
                        ->format(FluidEmail::FORMAT_BOTH) // send HTML and plaintext mail
                        ->setTemplate(SendService::INFORM_CANCEL_LENDING_MAIL_TEMPLATE)
                        ->assign('availabilityRequest', $myLending)
                        ->assign('language', $language)
                        ->assign('user', $receiver)
                    ;
                    GeneralUtility::makeInstance(MailerInterface::class)->send($fluidEmail);
                }
            }
        }
    }


    /**
     * @param Lending $lending
     * @param int[] $frontendUserStorageUids
     * @param RequestInterface $request
     * @param string $language
     * @return void
     */
    public function sendApproverEMails(Lending $lending, int $pageId, array $frontendUserStorageUids, RequestInterface $request, string $language): void
    {

        $approverGroup = $this->frontendUserService->getTopGroups($lending->getObject()->getApproverGroup());

        $receivers = [];

        /** @var FrontendUser $receiver */
        foreach ($this->frontendUserRepository->getUsersOfGroups($approverGroup, $frontendUserStorageUids) as $receiver) {
            if (!empty($receiver->getEmail())) {
                $receivers[] = $receiver;
            }
        }

        if (!empty($receivers)) {
            $fluidEmail = GeneralUtility::makeInstance(FluidEmail::class);
            $fluidEmail
                ->setRequest($request)
                ->from(new Address(
                    MailUtility::getSystemFromAddress(),
                    LocalizationUtility::translate('message.approver.email.senderName', SendService::EXTENSION_NAME)
                ))
                ->subject(LocalizationUtility::translate('message.approver.email.subject', SendService::EXTENSION_NAME))
                ->format(FluidEmail::FORMAT_BOTH) // send HTML and plaintext mail
                ->setTemplate(SendService::APPROVER_MAIL_TEMPLATE)
                ->assign('availabilityRequest', $lending)
                ->assign('language', $language)
                ->assign('pageUid', $pageId)
            ;
            foreach ($receivers as $receiver) {
                $fluidEmail->addBcc(new Address($receiver->getEmail(), $receiver->getFirstName() . ' ' . $receiver->getLastName()));
            }
            GeneralUtility::makeInstance(MailerInterface::class)->send($fluidEmail);
        }

    }


    /**
     * @param Lending $availabilityRequest
     * @param RequestInterface $request
     * @param string $language
     * @return void
     */
    public function informPreviousBorrower(Lending $availabilityRequest, RequestInterface $request, string $language): void
    {
        $receiver = $availabilityRequest->getBorrower();
        if (!empty($receiver->getEmail())) {
            $fluidEmail = GeneralUtility::makeInstance(FluidEmail::class);
            $fluidEmail
                ->setRequest($request)
                ->to(new Address($receiver->getEmail(), $receiver->getFirstName() . ' ' . $receiver->getLastName()))
                ->from(new Address(
                    MailUtility::getSystemFromAddress(),
                    LocalizationUtility::translate('message.informPreviousBorrower.email.senderName', SendService::EXTENSION_NAME)
                ))
                ->subject(LocalizationUtility::translate('message.informPreviousBorrower.email.subject', SendService::EXTENSION_NAME))
                ->format(FluidEmail::FORMAT_BOTH) // send HTML and plaintext mail
                ->setTemplate(SendService::INFORM_PREVIOUS_BORROWER_MAIL_TEMPLATE, )
                ->assign('user', $receiver)
                ->assign('language', $language)
                ->assign('availabilityRequest', $availabilityRequest)
            ;
            GeneralUtility::makeInstance(MailerInterface::class)->send($fluidEmail);
        }
    }

    /**
     * @param \Cylancer\CyLending\Domain\Model\Lending $availabilityRequest
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
     * @param string $language
     * @return void
     */
    public function sendAvailabilityRequestResultMail(Lending $availabilityRequest, RequestInterface $request, string $language): void
    {
        $receiver = $availabilityRequest->getBorrower();
        if (!empty($receiver->getEmail())) {
            $fluidEmail = GeneralUtility::makeInstance(FluidEmail::class);
            $fluidEmail
                ->setRequest($request)
                ->to(new Address($receiver->getEmail(), $receiver->getFirstName() . ' ' . $receiver->getLastName()))
                ->from(new Address(MailUtility::getSystemFromAddress(), LocalizationUtility::translate('message.borrower.email.senderName', SendService::EXTENSION_NAME)))
                ->subject(LocalizationUtility::translate('message.borrower.email.subject', SendService::EXTENSION_NAME))
                ->format(FluidEmail::FORMAT_BOTH) // send HTML and plaintext mail
                ->setTemplate(SendService::AVAILABILITY_REQUEST_RESULT_MAIL_TEMPLATE, )
                ->assign('user', $receiver)
                ->assign('language', $language)
                ->assign('availabilityRequest', $availabilityRequest)
            ;
            GeneralUtility::makeInstance(MailerInterface::class)->send($fluidEmail);
        }
    }

    /**
     * @param Lending $availabilityRequest
     * @param int[] $frontendUserStorageUids
     * @param RequestInterface $request
     * @param string $language
     * @return void
     */
    public function informObserverGroup(Lending $availabilityRequest, array $frontendUserStorageUids, RequestInterface $request, string $language): void
    {
        $observers = $availabilityRequest->getObject()->getObserverGroup();
        if ($observers != null) {
            $groups = $this->frontendUserService->getTopGroups($observers);
            $receivers = [];
            /** @var FrontendUser $receiver */
            foreach ($this->frontendUserRepository->getUsersOfGroups($groups, $frontendUserStorageUids) as $receiver) {
                if (!empty($receiver->getEmail())) {
                    $receivers[] = $receiver;
                }
            }
            if (!empty($receivers)) {
                $fluidEmail = GeneralUtility::makeInstance(FluidEmail::class);
                $fluidEmail
                    ->setRequest($request)
                    ->from(new Address(MailUtility::getSystemFromAddress(), LocalizationUtility::translate('message.observer.email.senderName', SendService::EXTENSION_NAME)))
                    ->subject(LocalizationUtility::translate('message.observer.email.subject', SendService::EXTENSION_NAME))
                    ->format(FluidEmail::FORMAT_BOTH) // send HTML and plaintext mail
                    ->setTemplate(SendService::OBSERVER_MAIL_TEMPLATE, )
                    ->assign('language', $language)
                    ->assign('availabilityRequest', $availabilityRequest)
                ;
                foreach ($receivers as $receiver) {
                    $fluidEmail->addBcc(new Address($receiver->getEmail(), $receiver->getFirstName() . ' ' . $receiver->getLastName()));
                }
                GeneralUtility::makeInstance(MailerInterface::class)->send($fluidEmail);
            }
        }
    }


}