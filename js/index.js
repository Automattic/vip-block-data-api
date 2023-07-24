/**
 * WordPress dependencies
 */
import { createBlock, serialize } from '@wordpress/blocks';
import domReady from '@wordpress/dom-ready';

const main = () => {
	// const blockData = [{"name":"core\/paragraph","attributes":{"content":"Welcome to WordPress. This is your first post. Edit or delete it, then start writing!","dropCap":false}}];
	const blockData = [{"name":"core\/heading","attributes":{"content":"h2 inside","level":2}},{"name":"core\/media-text","attributes":{"mediaId":14,"mediaLink":"http:\/\/block-data-api.vipdev.lndo.site\/?attachment_id=14","mediaType":"image","align":"wide","mediaAlt":"","mediaPosition":"left","mediaUrl":"http:\/\/block-data-api.vipdev.lndo.site\/wp-content\/uploads\/2023\/04\/omYGHPwGEU-1024x683.jpg","mediaWidth":50,"isStackedOnMobile":true},"innerBlocks":[{"name":"core\/heading","attributes":{"level":3,"content":"h3 in media-text"}},{"name":"core\/paragraph","attributes":{"content":"Some content in media-text","dropCap":false}}]},{"name":"core\/separator","attributes":{"opacity":"alpha-channel"}},{"name":"core\/columns","attributes":{"isStackedOnMobile":true},"innerBlocks":[{"name":"core\/column","attributes":{},"innerBlocks":[{"name":"core\/paragraph","attributes":{"content":"Paragraph on left side with a list:","dropCap":false}},{"name":"core\/list","attributes":{"ordered":false,"values":""},"innerBlocks":[{"name":"core\/list-item","attributes":{"content":"Item 1"}},{"name":"core\/list-item","attributes":{"content":"Item 2"},"innerBlocks":[{"name":"core\/list","attributes":{"ordered":false,"values":""},"innerBlocks":[{"name":"core\/list-item","attributes":{"content":"Subitem 1"}},{"name":"core\/list-item","attributes":{"content":"Subitem 2"}}]}]}]},{"name":"core\/paragraph","attributes":{"content":"","dropCap":false}}]},{"name":"core\/column","attributes":{},"innerBlocks":[{"name":"core\/code","attributes":{"content":"\/\/ Code listing\n{\n  \"a\": 1,\n  \"b\": 2,\n}"}}]}]},{"name":"core\/separator","attributes":{"opacity":"alpha-channel"}},{"name":"core\/quote","attributes":{"value":"","citation":"~ me, 2023"},"innerBlocks":[{"name":"core\/paragraph","attributes":{"content":"Final quote","dropCap":false}}]},{"name":"core\/html","attributes":{"content":"<p>hi<\/p>"}}];

	const blocks = createBlockMap(blockData);
	const blockHtml = serialize(blocks, { isInnerBlocks: true });

	console.log("blockHtml: ", blockHtml);
};

const createBlockMap = (blockData) => {
	return blockData.map((block) => {
		let innerBlocks = [];

		if (block.innerBlocks) {
			innerBlocks = createBlockMap(block.innerBlocks);
		}

		return createBlock(block.name, block.attributes, innerBlocks);
	}, []);
};

domReady( main );
