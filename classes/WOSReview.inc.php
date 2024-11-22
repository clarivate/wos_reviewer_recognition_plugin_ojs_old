<?php

/**
 * @file plugins/generic/webOfScience/classes/WOSReview.inc.php
 *
 * Copyright (c) 2024 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @class WOSReviews
 * @ingroup plugins_generic_webOfScience
 * @see WOSReviewsDAO
 *
 * @brief Basic class describing a reviews into the Web of Science.
 */

class WOSReview extends DataObject {

	//
	// Get/set methods
	//

	/**
	 * Get the submission ID of the referral.
	 * @return int
	 */
	function getSubmissionId() {
		return $this->getData('submissionId');
	}

	/**
	 * Set the submission ID of the Web of Science reviews.
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		return $this->setData('submissionId', $submissionId);
	}

	/**
	 * Get the journal ID of the Web of Science reviews.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * Set the journal ID of the Web of Science reviews.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * Get the reviewer ID of the Web of Science reviews.
	 * @return int
	 */
	function getReviewerId() {
		return $this->getData('reviewerId');
	}

	/**
	 * Set the journal ID of the Web of Science reviews.
	 * @param $reviewerId int
	 */
	function setReviewerId($reviewerId) {
		return $this->setData('reviewerId', $reviewerId);
	}

	/**
	 * Get the review ID of the Web of Science review.
	 * @return int
	 */
	function getReviewId() {
		return $this->getData('reviewId');
	}

	/**
	 * Set the review ID of the Web of Science review.
	 * @param $reviewId int
	 */
	function setReviewId($reviewId) {
		return $this->setData('reviewId', $reviewId);
	}

	/**
	 * Get the date added a review into the Web of Science.
	 * @return date
	 */
	function getDateAdded() {
		return $this->getData('dateAdded');
	}

	/**
	 * Set the date added a review into the Web of Science.
	 * @param $dateAdded date
	 */
	function setDateAdded($dateAdded) {
		return $this->setData('dateAdded', $dateAdded);
	}

	/**
	 * Get the localized title of the article.
	 * @return string
	 */
	function getLocalizedTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * Get the title of the article.
	 * @param $locale string
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}

	/**
	 * Set the title of the article.
	 * @param $title string
	 * @param $locale string
	 */
	function setTitle($title, $locale) {
		return $this->setData('title', $title?$title:'', $locale);
	}

	/**
	 * Get the title_en of the article.
	 * @return string
	 */
	function getTitleEn() {
		return $this->getData('titleEn');
	}

	/**
	 * Set the title_en of the article.
	 * @param $title string
	 * @param $locale string
	 */
	function setTitleEn($title) {
		return $this->setData('titleEn', $title);
	}

	/**
	 * Get the content of the review for the Web of Science.
	 * @return string
	 */
	function getLocalizedContent() {
		return $this->getLocalizedData('content');
	}

	/**
	 * Get the content of the review for the Web of Science.
	 * @param $locale string
	 * @return string
	 */
	function getContent($locale) {
		return $this->getData('content', $locale);
	}

	/**
	 * Set the content of the review for the Web of Science.
	 * @param $content string
	 * @param $locale string
	 */
	function setContent($content, $locale) {
		return $this->setData('content', $content?$content:'', $locale);
	}

}

?>
