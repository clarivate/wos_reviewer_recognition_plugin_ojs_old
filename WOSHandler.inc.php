<?php

/**
 * @file plugins/generic/webOfScience/WOSHandler.inc.php
 *
 * Copyright (c) 2024 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @class WOSHandler
 * @ingroup plugins_generic_webOfScience
 *
 * @brief Handle Web of Science requests
 */

import('pages.reviewer.ReviewerHandler');
import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');

class WOSHandler extends Handler {

    /** @var WebOfSciencePlugin The Web of Science plugin */

    static $plugin;

    static function setPlugin($plugin) {
        self::$plugin = $plugin;
    }

    /**
     * Confirm you want to export the review (GET) then export it (POST)
     * @param array $args
     * @param Request $request
     */
    function exportReview($args, $request) {
        $plugin =self::$plugin;
        $templateMgr =& TemplateManager::getManager();
        $templateMgr->addStyleSheet('wos-base', $request->getBaseUrl() . '/' . $plugin->getStyleSheet());

        $reviewId = intval($args[0]);

        $WOSReviewsDAO =& DAORegistry::getDAO('WOSReviewsDAO');
        $submissionCommentDao =& DAORegistry::getDAO('SubmissionCommentDAO');
        $reviewerSubmissionDao =& DAORegistry::getDAO('ReviewerSubmissionDAO');

        $exported =& $WOSReviewsDAO->getWOSReviewsIdByReviewId($reviewId);

        $reviewSubmission = $reviewerSubmissionDao->getReviewerSubmission($reviewId);

        $reviewerId = $reviewSubmission->getReviewerId();

        $user =& $request->getUser();

        if ($exported) {
            // Check that the review hasn't been exported already
            $templateMgr->assign('info', __('plugins.generic.wosrrs.export.error.alreadyExported'));
            return $templateMgr->fetchJson($plugin->getTemplatePath() . 'exportResults.tpl');

        } elseif (($reviewSubmission->getRecommendation() === null) || ($reviewSubmission->getRecommendation() === '')) {
            // Check that the review has been submitted to the editor
            $templateMgr->assign('info', __('plugins.generic.wosrrs.export.error.reviewNotSubmitted'));
            return $templateMgr->fetchJson($plugin->getTemplatePath() . 'exportResults.tpl');

        } elseif ($user->getId() !== $reviewerId) {
            // Check that user is person who wrote review
            $templateMgr->assign('info', __('plugins.generic.wosrrs.export.error.invalidUser'));
            return $templateMgr->fetchJson($plugin->getTemplatePath() . 'exportResults.tpl');
        }

        if ($request->isGet()) {

            $router = $request->getRouter();
            $templateMgr->assign('reviewId', $reviewId);
            $templateMgr->assign('pageURL', $router->url($request, null, null, 'exportReview', array('reviewId' =>  $reviewId)));
            return $templateMgr->fetchJson($plugin->getTemplatePath() . 'wosExportReviewForm.tpl');
        }
        elseif ($request->isPost())
        {
            $journalId = $reviewSubmission->getJournalId();
            $submissionId = $reviewSubmission->getId();
            $rtitle = $reviewSubmission->getLocalizedTitle();
            $rtitle_en = $reviewSubmission->getTitle('en_US');
            $rname = $user->getFullName();
            $remail = $user->getEmail();

            $reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
            $reviewAssignment = $reviewAssignmentDao->getById($reviewId);

            $body = '';

            // Get the comments associated with this review assignment
            $submissionComments = $submissionCommentDao->getSubmissionComments($reviewSubmission->getId(), COMMENT_TYPE_PEER_REVIEW, $reviewAssignment->getId());

            if($submissionComments) {
                foreach ($submissionComments->toArray() as $comment) {
                    // If the comment is viewable by the author, then add the comment.
                    if ($comment->getViewable()) $body .= PKPString::html2text($comment->getComments()) . "\n";
                }
            }

            if ($reviewFormId = $reviewAssignment->getReviewFormId()) {

                $reviewId = $reviewAssignment->getId();
                $reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');
                $reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
                $reviewFormElements = $reviewFormElementDao->getByReviewFormId($reviewFormId)->toArray();

                foreach ($reviewFormElements as $reviewFormElement) if ($reviewFormElement->getIncluded()) {

                    $body .= PKPString::html2text($reviewFormElement->getLocalizedQuestion()) . ": \n";
                    $reviewFormResponse = $reviewFormResponseDao->getReviewFormResponse($reviewId, $reviewFormElement->getId());

                    if ($reviewFormResponse) {

                        $possibleResponses = $reviewFormElement->getLocalizedPossibleResponses();
                        if (in_array($reviewFormElement->getElementType(), $reviewFormElement->getMultipleResponsesElementTypes())) {
                            if ($reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES) {
                                foreach ($reviewFormResponse->getValue() as $value) {
                                    $body .= "\t" . PKPString::html2text($possibleResponses[$value-1]['content']) . "\n";
                                }
                            } else {
                                $body .= "\t" . PKPString::html2text($possibleResponses[$reviewFormResponse->getValue()-1]['content']) . "\n";
                            }
                            $body .= "\n";
                        } else {
                            $body .= "\t" . $reviewFormResponse->getValue() . "\n\n";
                        }
                    }
                }
            }

            $body = str_replace("\r", '', $body);
            $body = str_replace("\n", '\r\n', $body);

            $auth_key = $plugin->getSetting($journalId, 'auth_key');
            $auth_token = $plugin->getSetting($journalId, 'auth_token');

            $plugin->import('classes.WOSReview');

            $dateRequested = new DateTime($reviewAssignment->getDateNotified());
            $dateCompleted = new DateTime($reviewAssignment->getDateCompleted());

            $locale = AppLocale::getLocale();

            $WOSReview = new WOSReview();

            $WOSReview->setJournalId($journalId);
            $WOSReview->setSubmissionId($submissionId);
            $WOSReview->setReviewerId($reviewerId);
            $WOSReview->setReviewId($reviewId);
            $WOSReview->setTitleEn($rtitle_en);
            $WOSReview->setDateAdded(Core::getCurrentDate());

            $WOSReview->setTitle($rtitle, $locale);
            $WOSReview->setContent($body, $locale);


            $WOSReviewsDAO = new WOSReviewsDAO();
            DAORegistry::registerDAO('WOSReviewsDAO', $WOSReviewsDAO);

            $headers = array(
                "Authorization: Token ". $auth_token,
                'Content-Type: application/json'
            );

            $data = array();
            $data["key"] = $auth_key;
            $data["reviewer"]["name"] = $rname;
            $data["reviewer"]["email"] = $remail;
            $data["publication"]["title"] = $rtitle;
            $data["publication"]["abstract"] = $reviewSubmission->getLocalizedAbstract();
            $data["request_date"]["day"] = $dateRequested->format('d');
            $data["request_date"]["month"] = $dateRequested->format('m');
            $data["request_date"]["year"] = $dateRequested->format('Y');
            $data["complete_date"]["day"] = $dateCompleted->format('d');
            $data["complete_date"]["month"] = $dateCompleted->format('m');
            $data["complete_date"]["year"] = $dateCompleted->format('Y');

            // Don't send content if it is empty
            if ($body !== '')
                $data["content"] = $body;

            $json_data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $json_data = str_replace("\\\\", '\\', $json_data);

            $templateMgr->assign('json_data',$json_data);

            $url = "https://publons.com/api/v2/review/";

            $options = array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $json_data
            );

            $returned = $this->_curlPost($options);

            // If success then save into database
            if (($returned['status'] >= 200) && ($returned['status'] < 300)){
                $WOSReviewsDAO->insertObject($WOSReview);
            }

            $templateMgr->assign('status',$returned['status']);

            if ($returned['status'] == 201){
                $templateMgr->assign('serverAction',$returned['result']['action']);
                $claimUrl = "https://www.webofscience.com/wos/op/review-claim/integration/" . $returned['result']['token'];
                $templateMgr->assign('claimURL', $claimUrl);
            }

            $templateMgr->assign('result',$returned['result']);
            $templateMgr->assign('error', $returned['error']);
            return $templateMgr->fetchJson($plugin->getTemplatePath() . 'exportResults.tpl');
        }

    }

    /**
     * Post a request to a resource using CURL
     * @param $url string
     * @param $headers array
     * @return array
     */
    function _curlPost($curlopt) {

        $curl = curl_init();
        curl_setopt_array($curl, $curlopt);

        $httpResult = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $httpError = curl_error($curl);
        curl_close ($curl);

        return array(
            'status' => $httpStatus,
            'result' => json_decode($httpResult, true),
            'error'  => $httpError
        );
    }

    /**
     * Get whether curl is available
     * @return boolean
     */
    function curlInstalled() {
        return function_exists('curl_version');
    }
}

?>
