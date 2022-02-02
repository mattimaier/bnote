<?php

/**
 * Interface definition for BNote App (BNA) connection.
 * @author Matti
 *
 */
interface iBNA {
	
	/**
	 * @return Returns all rehearsals.
	 */
	public function getRehearsals();
	
	/**
	 * @return Returns all concerts.
	 */
	public function getConcerts();
	
	/**
	 * @return Returns all contacts.
	 */
	public function getContacts();
	
	/**
	 * @return Returns all members.
	 */
	public function getMembers();
	
	/**
	 * @return Returns all groups without members.
	 */
	public function getGroups();
	
	/**
	 * @return Returns all locations.
	 */
	public function getLocations();
	
	/**
	 * @return Returns all tasks for this user.
	 */
	public function getTasks();
	
	/**
	 * @return Returns the news.
	 */
	public function getNews();
	
	/**
	 * @return Returns all votes for this user.
	 */
	public function getVotes();
	
	/**
	 * Retrieves all possible options for the given vote.
	 * @param Integer $vid Vote ID.
	 * @return All vote options for this vote.
	 */
	public function getVoteOptions($vid);
	
	/**
	 * Retrieves the result of a vote.
	 * @param Integer $vid Vote ID.
	 * @return Array with all options and their counts.
	 */
	public function getVoteResult($vid);
	
	/**
	 * @return Returns all songs in the repertoire.
	 */
	public function getSongs();
	
	/**
	 * @return Returns all genres.
	 */
	public function getGenres();
	
	/**
	 * @return Returns all statuses.
	 */
	public function getStatuses();
	
	/**
	 * <b>Use this function only to fetch all data for a user once!</b>
	 * @return Returns all rehearsals, concerts, contacts, location and so on.
	 */
	public function getAll();
	
	/**
	 * Retrieves all comments for the given object.
	 * @param char $otype R=Rehearsal, C=Concert, V=Vote
	 * @param Integer $oid ID of the object to comment on.
	 * @return All comments for the given object.
	 */
	public function getComments($otype, $oid);
	
	/**
	 * Gets the participation choice of a user for a rehearsal.
	 * @param Integer $rid Rehearsal ID
	 * @param Integer $uid User ID
	 * @return 1 if the user participates, 0 if not, -1 if not chosen yet.
	 */
	public function getRehearsalParticipation($rid, $uid);
	
	/**
	 * Saves the participation of a user in a rehearsal.
	 * @param Integer $rid Rehearsal ID
	 * @param Integer $uid User ID
	 * @param Integer $part 1=participates, 0=does not participate, 2=maybe participates
	 * @param String $reason Optional parameter to give a reason for not participating.
	 */
	public function setRehearsalParticipation($rid, $uid, $part, $reason);
	
	/**
	 * Saves the participation of a user in a concert.
	 * @param Integer $cid Concert ID
	 * @param Integer $uid User ID
	 * @param Integer $part 1=participates, 0=does not participate, 2=maybe participates
	 * @param String $reason Optional parameter to give a reason for not participating.
	 */
	public function setConcertParticipation($cid, $uid, $part, $reason);
	
	/**
	 * Set a task as completed. (POST)
	 * @param int $tid Task ID.
	 */
	public function taskCompleted($tid);
	
	/**
	 * Adds a song to the repertoire. (POST)
	 * @param String $title Title of the song.
	 * @param String $length Lenght in format mm:ss.
	 * @param Integer $bpm Beats per Minute.
	 * @param String $music_key Musical key of the song.
	 * @param String $notes Additional Notes to the song.
	 * @param Integer $genre Genre ID.
	 * @param String $composer Name of the composer.
	 * @param Integer $status Status ID.
	 * @return The ID of the new song.
	 */
	public function addSong($title, $length, $bpm, $music_key, $notes, $genre, $composer, $status);
	
	/**
	 * Adds a rehearsal. (POST)
	 * @param String $begin Begin of the rehearsal, format: YYYY-MM-DD HH:ii:ss.
	 * @param String $end End of the rehearsal, format: YYYY-MM-DD HH:ii:ss.
	 * @param String $approve_until Approve participation until, format: YYYY-MM-DD HH:ii:ss.
	 * @param String $notes Notes for the rehearsal.
	 * @param Integer $location Location ID.
	 * @param Array $groups List of groups the rehearsal belongs to.
	 * @return The ID of the new rehearsal.
	 */
	public function addRehearsal($begin, $end, $approve_until, $notes, $location, $groups);
	
	/**
	 * Adds a concert. (POST)
	 * @param String $begin Begin of the concert, format: YYYY-MM-DD HH:ii:ss.
	 * @param String $end End of the concert, format: YYYY-MM-DD HH:ii:ss.
	 * @param String $approve_until Approve participation until, format: YYYY-MM-DD HH:ii:ss.
	 * @param String $notes Notes for the concert.
	 * @param Integer $location Location ID.
	 * @param Integer $program Program ID.
	 * @param Integer $contact Contact ID.
	 * @param Array $groups List of groups the concert belongs to.
	 * @return The ID of the new concert.
	 */
	public function addConcert($begin, $end, $approve_until, $notes, $location, $program, $contact, $groups);
	
	/**
	 * Updates a rehearsal. (POST)
	 * @param Integer $id Rehearsal ID.
	 * @param String $begin Begin of the rehearsal, format: YYYY-MM-DD HH:ii:ss.
	 * @param String $end End of the rehearsal, format: YYYY-MM-DD HH:ii:ss.
	 * @param String $approve_until Approve participation until, format: YYYY-MM-DD HH:ii:ss.
	 * @param String $notes Notes for the rehearsal.
	 * @param Integer $location Location ID.
	 * @param Array $groups List of groups the rehearsal belongs to.
	 * @return The ID of the rehearsal.
	 */
	public function updateRehearsal($id, $begin, $end, $approve_until, $notes, $location, $groups);
	
	/**
	 * Updates a concert. (POST)
	 * $param Integer $id Concert ID.
	 * @param String $begin Begin of the concert, format: YYYY-MM-DD HH:ii:ss.
	 * @param String $end End of the concert, format: YYYY-MM-DD HH:ii:ss.
	 * @param String $approve_until Approve participation until, format: YYYY-MM-DD HH:ii:ss.
	 * @param String $notes Notes for the concert.
	 * @param Integer $location Location ID.
	 * @param Integer $program Program ID.
	 * @param Integer $contact Contact ID.
	 * @param Array $groups List of groups the concert belongs to.
	 * @return The ID of the concert.
	 */
	public function updateConcert($id, $begin, $end, $approve_until, $notes, $location, $program, $contact, $groups);
	
	/**
	 * Deletes a rehearsal. (POST)
	 * @param Integer $id Rehearsal ID.
	 * @return boolean True when successfully deleted, otherwise false.
	 */
	public function deleteRehearsal($id);
	
	/**
	 * Deletes a concert. (POST)
	 * @param Integer $id Concert ID.
	 * @return boolean True when successfully deleted, otherwise false.
	 */ 
	public function deleteConcert($id);
	
	/**
	 * Adds a vote to the voting. (POST)
	 * @param Integer $vid ID of the voting.
	 * @param Array $options Options in format: [vote_option id] => [0 as no, 1 as yes, 2 as maybe].
	 */
	public function vote($vid, $options);
	
	/**
	 * Adds a comment to an object. (POST)
	 * @param char $otype R=Rehearsal, C=Concert, V=Vote
	 * @param Integer $oid ID of the object to comment on.
	 * @param String $message Urlencoded message.
	 * @return ID of the newly created comment.
	 */
	public function addComment($otype, $oid, $message); 
	
	/**
	 * Retrives the version of this BNote instance.
	 * @return Version as a string.
	 */
	public function getVersion();
	
	/**
	 * Retrives information on the currently registered user.
	 * The user is identified using the PIN.
	 * @return Array with contact information.
	 */
	public function getUserInfo();
	
	/**
	 * Retrives the Mobile PIN for the given credentials in case they are correct.
	 * <i>This method can be used without the need of a mobile PIN!</i>
	 * @param string $login Login or E-Mail of the user.
	 * @param string $password Password of the user.
	 * @return Mobile PIN as string.
	 */
	public function mobilePin($login, $password);
	
	/**
	 * Checks whether the user has access to a given module.
	 * @return Boolean value true (has access) or false (no access).
	 */
	public function hasUserAccess();
	
	/**
	 * Retrives all songs to practise from a rehearsal.
	 * @param Integer $rid Rehearsal ID.
	 * @return Array with songs to practise and their information.
	 */
	public function getSongsToPractise($rid);
	
	/**
	 * Sends an email to all members of the specified groups.
	 * @param String $subject Mail subject.
	 * @param String $body Body of the mail.
	 * @param Array $groups Groups the mail is sent to.
	 */
	public function sendMail($subject, $body, $groups);
	
	/**
	 * Updates a song in the repertoire using the POST array.
	 * @param Integer $status Status ID.
	 */
	public function updateSong($id);
	
	/**
	 * Gets just one song from the database.
	 * @param Integer $id Song ID.
	 */
	public function getSong($id);
	
	/**
	 * Deletes this song from the database.
	 * @param Integer $id Song ID.
	 */
	public function deleteSong($id);
	
	/**
	 * Reads all equipment from the database and returns it.
	 * @return Array with equipment.
	 */
	public function getEquipment();
	
	/**
	 * Adds the equipment to the database using the $_POST array.
	 */
	public function addEquipment();
	
	/**
	 * Update equipment in the system using the POST array.
	 */
	public function updateEquipment($id);
	
	/**
	 * Removes the equipment from the database.
	 * @param Integer $id Equipment ID.
	 */
	public function deleteEquipment($id);
	
	/**
	 * Adds a reservation in the database using the $_POST array.
	 */
	public function addReservation();
	
	/**
	 * Deletes the reservation from the database.
	 * @param Integer $id Reservation ID.
	 */
	public function deleteReservation($id);
	
	/**
	 * Updates the reservation from the $_POST array.
	 * @param Integer $id Reservation ID.
	 */
	public function updateReservation($id);
	
	/**
	 * Retrieves the reservation details for the given ID.
	 * @param Integer $id Reservation ID.
	 */
	public function getReservation($id);
	
	/**
	 * Retrieves all future reservations from the database.
	 */
	public function getReservations();
	
	/**
	 * Adds a contact given the $_POST array data.
	 */
	public function addTask();
	
	/**
	 * Adds a location given the $_POST array data.
	 */
	public function addLocation();
	
	/**
	 * Adds a contact given the $_POST array data.
	 */
	public function addContact();
	
	/**
	 * Retrieves all instruments available in the system.
	 */
	public function getInstruments();
	
	/**
	 * Creates a user and a linked contact with the given POST data.
	 * @return Integer User ID.
	 */
	public function signup();
	
	/**
	 * Retrieves the given program with its songs in order.
	 * @param integer $id Program ID.
	 */
	public function getProgram($id);
}

// Abstract Implementation of BNote Application Interface
include "bna-abstract.php";

?>