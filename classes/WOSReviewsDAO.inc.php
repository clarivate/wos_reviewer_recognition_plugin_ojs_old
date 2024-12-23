<?php

/**
 * @file plugins/generic/webOfScience/classes/WOSReviewsDAO.inc.php
 *
 * Copyright (c) 2024 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * @class WOSReviewsDAO
 * @ingroup plugins_generic_webOfScience
 * @see WOSReview
 *
 * @brief Operations for retrieving and modifying WOSReview objects.
 */
import('lib.pkp.classes.db.DAO');

class WOSReviewsDAO extends DAO {

    /**
     * Retrieve a Web of Science reviews by ID.
     * @param $wosReviewId int
     * @return WOSReview
     */
    function &getWOSReviews($wosReviewId) {
        $result =& $this->retrieve(
            'SELECT * FROM publons_reviews WHERE publons_reviews_id = ?', $wosReviewId
        );

        $returner = null;
        if ($result->RecordCount() != 0) {
            $returner =& $this->_returnWOSReviewsFromRow($result->GetRowAssoc(false));
        }
        return $returner;
    }

    /**
     * Get a list of localized field names
     * @return array
     */
    function getLocaleFieldNames() {
        return array('title', 'content');
    }

    /**
     * Internal function to return a WOSReview object from a row.
     * @param $row array
     * @return WOSReview
    */
    function &_returnWOSReviewsFromRow(&$row) {
        $WOSReview = new WOSReview();
        $WOSReview->setId($row['publons_reviews_id']);
        $WOSReview->setJournalId($row['journal_id']);
        $WOSReview->setSubmissionId($row['submission_id']);
        $WOSReview->setReviewerId($row['reviewer_id']);
        $WOSReview->setReviewId($row['review_id']);
        $WOSReview->setTitleEn($row['title_en']);
        $WOSReview->setDateAdded($this->datetimeFromDB($row['date_added']));

        $this->getDataObjectSettings('publons_reviews_settings', 'publons_reviews_id', $row['publons_reviews_id'], $WOSReview);

        return $WOSReview;
    }

    /**
     * Insert a new review into the Web of Science.
     * @param $WOSReview WOSReview
     * @return int
     */
    function insertObject(&$WOSReview) {
        $ret = $this->update(
            sprintf('
                INSERT INTO publons_reviews
                    (journal_id,
                    submission_id,
                    reviewer_id,
                    review_id,
                    title_en,
                    date_added)
                VALUES
                    (?, ?, ?, ?, ?, %s)',
                $this->datetimeToDB($WOSReview->getDateAdded())
            ),
            array(
                $WOSReview->getJournalId(),
                $WOSReview->getSubmissionId(),
                $WOSReview->getReviewerId(),
                $WOSReview->getReviewId(),
                $WOSReview->getTitleEn()
            )
        );
        $WOSReview->setId($this->getInsertObjectId());
        $this->updateLocaleFields($WOSReview);

        return $WOSReview->getId();
    }

    /**
     * Update the localized settings for this object
     * @param $WOSReview WOSReview
     */
    function updateLocaleFields(&$WOSReview) {
        $this->updateDataObjectSettings('publons_reviews_settings', $WOSReview, array(
            'publons_reviews_id' => $WOSReview->getId()
        ));
    }

    /**
     * Update an existing data about reviews, publishing into the Web of Science.
     * @param $WOSReview WOSReview object
     * @return boolean
     */
    function updateObject(&$WOSReview) {
        $returner = $this->update(
            sprintf('UPDATE publons_reviews
                SET journal_id = ?,
                    submission_id = ?,
                    reviewer_id = ?,
                    review_id = ?,
                    title_en = ?,
                    date_added = %s
                WHERE   publons_reviews_id = ?',
                $this->datetimeToDB($WOSReview->getDateAdded())
            ),
            array(
                (int) $WOSReview->getJournal(),
                (int) $WOSReview->getSubmissionId(),
                (int) $WOSReview->getReviewerId(),
                (int) $WOSReview->getReviewId(),
                $WOSReview->getTitleEn(),
                (int) $WOSReview->getId()
            )
        );
        $this->updateLocaleFields($WOSReview);
        return $returner;
    }

    /**
     * Delete a data about review into the Web of Science.
     * deleted.
     * @param $WOSReview WOSReview
     * @return boolean
     */
    function deleteObject($WOSReview) {
        return $this->deleteObjectById($WOSReview->getId());
    }

    /**
     * Delete an object by ID.
     * @param $WOSReviewId int
     * @return boolean
     */
    function deleteObjectById($WOSReviewId) {
        $this->update('DELETE FROM publons_reviews_settings WHERE publons_reviews_id = ?', (int) $WOSReviewId);
        return $this->update('DELETE FROM publons_reviews WHERE publons_reviews_id = ?', (int) $WOSReviewId);
    }

    /**
     * Get the ID of the last inserted review into Web of Science.
     * @return int
     */
    function getInsertObjectId() {
        return $this->_getInsertId('publons_reviews', 'publons_reviews_id');
    }


    /**
     * Return a submitted book for review id for a given article and journal.
     * @param $journalId int
     * @param $submissionId int
     * @param $reviewerId int
     * @return int
     */
    function getWOSReviewsIdByArticle($journalId, $submissionId, $reviewerId) {

        $result =& $this->retrieve(
            'SELECT publons_reviews_id
                FROM publons_reviews
                WHERE submission_id = ?
                AND journal_id = ?
                AND reviewer_id = ?',
            array(
                $submissionId,
                $journalId,
                $reviewerId
            )
        );

        $returner = isset($result->fields[0]) && $result->fields[0] != 0 ? $result->fields[0] : null;

        unset($result);

        return $returner;
    }

    /**
     * Return a submitted book for review id for a given article and journal.
     * @param $reviewId int
     * @return int
     */
    function getWOSReviewsIdByReviewId($reviewId) {

        $result =& $this->retrieve(
            'SELECT publons_reviews_id
                FROM publons_reviews
                WHERE review_id = ?',
            array(
                $reviewId
            )
        );

        $row = $result->current();
        $publons_reviews_id = $row ? $row->publons_reviews_id : null;

        unset($result);

        return $publons_reviews_id;
    }

    /**
     * Retrieve an iterator of WOSReview for a particular journal ID,
     * optionally filtering by status.
     * @param $journalId int
     * @return object DAOResultFactory containing matching WOSReview
     */
    function &getWOSReviewsByJournal($journalId, $rangeInfo = null, $sort) {
        $result =& $this->retrieveRange(
            "SELECT *
            FROM    publons_reviews
            WHERE   journal_id = ?
            ORDER BY '$sort'",
            $journalId,
            $rangeInfo
        );
        $returner = new DAOResultFactory($result, $this, '_returnWOSReviewsFromRow');
        return $returner;
    }
}

?>
