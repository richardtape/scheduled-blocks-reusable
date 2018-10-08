<?php

/**
 * Scheduled Blocks Reusable: Scheduled_Blocks_Reusable class
 *
 * @package scheduledblocksreusable
 * @since 0.2.0
 */

/**
 * Handles the reusable blocks within Gutenberg.
 *
 * @since 0.2.0
 */

class Scheduled_Blocks_Reusable {

	/**
	 * An array of html to remove AFTER gutenberg has parsed. This is mostly
	 * for reusable blocks.
	 *
	 * @var array
	 */
	public $remove_after = array();

	/**
	 * An array of data to remove BEFORE gutenberg has parsed.
	 *
	 * @var array
	 */
	public $remove_before = array();

	/**
	 * Our main initialization. Add our hooks.
	 *
	 * @return void
	 */
	public function init() {

		$this->add_hooks();

	}// end init()

	/**
	 * Add our actions and filters
	 *
	 * @return void
	 */
	public function add_hooks() {

		// Add 'block' to the parsed list of blocks
		add_filter( 'scheduled_blocks_valid_block_types', array( $this, 'scheduled_blocks_valid_block_types__add_block_type' ) );

		// Mark a reusable block so we're able to capture it later.
		add_filter( 'scheduled_blocks_get_usable_data_from_block_details_start', array( $this, 'scheduled_blocks_get_usable_data_from_block_details_start__mark_block_as_reusable' ) );

		// Hook in to where we have initially parsed reusable blocks and mark them as reusable here
		add_action( 'scheduled_blocks_get_usable_data_from_block_details_handle_special', array( $this, 'scheduled_blocks_get_usable_data_from_block_details_handle_reusable__mark_as_reusable' ) );

		// Filter the_content to remove scheduled reusable blocks (rather than scheduled blocks within a reusable block) before Gutenberg has parsed it
		add_filter( 'the_content', array( $this, 'the_content__filter_content_before_gutenberg' ), 7 );

		// Filter the_content to remove blocks (mostly reusable) after Gutenberg has parsed it
		add_filter( 'the_content', array( $this, 'the_content__filter_content_after_gutenberg' ), 15 );

	}// end add_hooks()

	/**
	 * Add the 'block' block type to the list of parsed blocks, so we're able to handle reusable
	 * blocks.
	 *
	 * @param array $block_types The currently parsed block types.
	 * @return array Modified block types.
	 */
	public function scheduled_blocks_valid_block_types__add_block_type( $block_types = array() ) {

		$block_types[] = 'block';

		return $block_types;

	}// end scheduled_blocks_valid_block_types__add_block_type()

	/**
	 * At the very top of Scheduled_Blocks->scheduled_blocks_get_usable_data_from_block_details() there
	 * is a filter which allows us to inspect each block passed to it. In this method, we're hooking
	 * in to that filter and marking reusable blocks as reusable, so we're then able to hook in later
	 * to remove them from the content.
	 *
	 * @param array $block_details
	 * @return void
	 */
	public function scheduled_blocks_get_usable_data_from_block_details_start__mark_block_as_reusable( $block_details = array() ) {

		// If the schedule is actually set on the reusable block itself, rather than the blocks
		// _within_ the reusable block, then we set reusable on the schedule block
		if ( isset( $block_details['attrs']['ref'] ) && ( isset( $block_details['attrs']['scheduledStart'] ) || ( isset( $block_details['attrs']['scheduledEnd'] ) ) ) ) {
			$block_details['reusable'] = true;
			return $block_details;
		}

		if ( isset( $block_details['attrs']['ref'] ) && ! empty( $block_details['attrs']['ref'] ) ) {

			$post_id_of_reusable_block = absint( $block_details['attrs']['ref'] );
			$post_content              = get_post_field( 'post_content', $post_id_of_reusable_block );
			$blocks                    = Scheduled_Blocks::scheduled_blocks_extract_scheduled_blocks_from_content( $post_content );

			if ( is_array( $blocks ) && ! empty( $blocks ) ) {
				foreach ( $blocks as $id => $block ) {
					$block['reusable'] = true;
					$block_details     = Scheduled_Blocks::scheduled_blocks_get_usable_data_from_block_details( $block );
				}
			}
		}

		return $block_details;

	}// end scheduled_blocks_get_usable_data_from_block_details_start__mark_block_as_reusable()

	/**
	 * When we have found a reusable block, we add it to an array so that we can handle it
	 * after Gutenberg has parsed the HTML.
	 *
	 * @param array $block_details The details of this block
	 * @return void
	 */
	public function scheduled_blocks_get_usable_data_from_block_details_handle_reusable__mark_as_reusable( $block_details ) {

		if ( ! isset( $block_details['reusable'] ) ) {
			return;
		}

		// We've already been around and found a reusable block. We now handle this separately.
		// We need to determine if this block within a reusable block should be removed. If so
		// we need to determine if this block should be removed after Gutenberg has parsed the
		// parent reusable block and places this particular child block in the content.
		if ( Scheduled_Blocks::block_should_be_removed( $block_details ) ) {

			if ( 'core/block' === $block_details['blockName'] ) {
				$this->remove_before[] = $block_details['attrs'];
				return;
			}

			$this->remove_after[] = $block_details['innerHTML'];
		}

	}// end scheduled_blocks_get_usable_data_from_block_details_handle_reusable__mark_as_reusable()

	/**
	 * Filter the_content before Gutenberg does to check if we have any scheduled reusable
	 * blocks (rather than any scheduled blocks within a reusable block). We should have
	 * data in $this->remove_before which contains the post ID of the reusable block as
	 * well as the schedule data
	 *
	 * @param string $content the post content
	 * @return string potentially adjusted post content
	 */
	public function the_content__filter_content_before_gutenberg( $content ) {

		// If we don't have anything saved here, then bail
		if ( ! is_array( $this->remove_before ) || empty( $this->remove_before ) ) {
			return $content;
		}

		// In the db (and $content right now) it looks like this: <!-- wp:block {"ref":112,"scheduledStart":"2018-10-16T16:34:16"} /-->
		// We need to convert the data we have in $this->remove_before to a string that looks like what is in the db so we can strip it.
		// it should always start with <!-- wp:block {"ref":{$ref}
		foreach ( $this->remove_before as $id => $attrs ) {
			$post_id = $attrs['ref'];
			$string  = '<!-- wp:block {"ref":' . $post_id;

			// We now look through the content for this string, up until the next /-->
			$regex = '(<!-- wp:block {"ref":112)[^}](.+)(\/-->)';
			preg_match( '/(<!-- wp:block {"ref":112)[^}](.+)(\/-->)/', $content, $matches );

			if ( ! is_array( $matches ) || empty( $matches ) ) {
				continue;
			}

			$content = str_replace( trim( $matches[0] ), '', $content );

		}

		return $content;

	}// end the_content__filter_content_before_gutenberg()

	/**
	 * Filter out any content we have remaining after Gutenberg has done its thing.
	 * This is mostly to remove reusable blocks that remained after our initial parsing.
	 *
	 * @param string $content the post's content after Gutenberg has done its thing
	 * @return string the re-parsed content
	 */
	public function the_content__filter_content_after_gutenberg( $content ) {

		// If we don't have anything saved here, then bail
		if ( ! is_array( $this->remove_after ) || empty( $this->remove_after ) ) {
			return $content;
		}

		// OK, we have some HTML to remove after Gutenberg.
		foreach ( $this->remove_after as $id => $html_to_remove ) {
			$content = Scheduled_Blocks::scheduled_blocks_remove_html_from_content( $html_to_remove, $content );
		}

		return $content;

	}//end the_content__filter_content_after_gutenberg()

}// end class Scheduled_Blocks_Reusable
