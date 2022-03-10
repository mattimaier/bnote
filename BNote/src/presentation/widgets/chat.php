<?php

/**
 * Shows the discussion and allows to add a new comment.
 * @author matti
 *
 */
class ChatWidget implements iWriteable {
	
	/**
	 * Object Type
	 * @var String
	 */
	private $otype;
	
	/**
	 * Object ID
	 * @var Integer
	 */
	private $oid;
	
	/**
	 * Application Data Provider handle
	 * @var ApplicationDataProvider
	 */
	private $adp;
	
	/**
	 * URL which is prefixed when adding comments, typically containing the modePrefix() segment.
	 * @var String
	 */
	private $addCommentPrefix;
	
	public function __construct($otype, $oid, $adp, $addCommentPrefix) {
		$this->otype = $otype;
		$this->oid = $oid;
		$this->adp = $adp;
		$this->addCommentPrefix = $addCommentPrefix;
	}
	
	public function getName() {
		return $this->otype . "_" . $this->oid . "_discussion";
	}

	public function write() {
		global $system_data;
		// check if feature is activated
		if($system_data->getDynamicConfigParameter("discussion_on") != 1) {
			new BNoteError(Lang::txt("StartView_discussion.Deactivated"));
		}
		?>
		<div class="chatbar p-2">
		<?php
		// show comments
		$comments = $this->adp->getDiscussion($this->otype, $this->oid);
		
		if(count($comments) == 1) {
			echo Lang::txt("StartView_discussion.noCommentsInDiscussion");
		}
		else {
			foreach($comments as $i => $comment) {
				if($i == 0) continue; // header
				
				$author = $comment["author"] . " / " . Data::convertDateFromDb($comment["created_at"]);
				?>
				<div class="chat_message_box mb-2 p-2">
					<span class="message_author"><?php echo $author; ?></span>
					<p>
					<?php echo urldecode($comment["message"]); ?>
					</p>
				</div>
				<?php
			}
		}
		
		// add comment form
		$submitLink = $this->addCommentPrefix . "&otype=" . $this->otype . "&oid=" . $this->oid;
		
		?>
		<hr>
		<form id="<?php echo $this->getName(); ?>" action="<?php echo $submitLink; ?>" method="POST" class="row row-cols-lg-auto align-items-center">
			<div class="col-12 pe-0">
				<input type="text" class="form-control" name="message" placeholder="<?php echo Lang::txt("ChatWidget_write.messagePlaceholder"); ?>" />
			</div>
			<div class="col-12 ps-1">
				<button type="submit" form="<?php echo $this->getName(); ?>" class="btn btn-primary"><i class="bi-send"></i></button>
			</div>
		</form>
		</div>
		<?php
	}

	
}