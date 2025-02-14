<?php
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	namespace PHP_CodeSniffer\Standards\HelloNasty\Sniffs\Comments;


	use PHP_CodeSniffer\Sniffs\Sniff;
	use PHP_CodeSniffer\Files\File;



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS DECLARATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class CommentSectionLeadingSpaceSniff implements Sniff
{

	public function register() {

		return array(T_COMMENT);
	}


	public function process(File $file, $stackPtr) {

		$tokens = $file->getTokens();
		$token = $tokens[$stackPtr];

		// skip if not a comment section
		if(!str_starts_with($token['content'],'/*'.str_repeat('/',50))) { return; }

		// comment section consists of 5 lines
		$nextPtr = $file->findNext(T_COMMENT, $stackPtr, null, true);
		$nextToken = $tokens[$nextPtr];
		if($nextToken['line'] - $token['line'] != 4) { return; }

		// find prev token before comment section
		$prevPtr = $file->findPrevious(T_COMMENT, $stackPtr, null, true);
		$prevToken = $tokens[$prevPtr];

		// skip if start of file
		if($prevToken['type'] == 'T_OPEN_TAG') { return; }

		// first token must be a T_WHITESPACE
		if($prevToken['type'] != 'T_WHITESPACE') {
			$file->addError('There must be 3 blank lines before a comment section', $stackPtr, 'CommentSection_LeadingSpace');
		}

		// 3 blank lines before comment section
		$prevPtr = $file->findPrevious(T_WHITESPACE, $prevPtr, null, true);
		$prevToken = $tokens[$prevPtr];
		if($token['line'] - $prevToken['line'] != 4) {
			$file->addError('There must be 3 blank lines before a comment section', $stackPtr, 'CommentSection_LeadingSpace');
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


}	// end class


