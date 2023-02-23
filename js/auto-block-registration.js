(() => {
    function get_all_blocks_names() {
        return wp.blocks.getBlockTypes().map( el => el.name );
    }

    async function api_check_unregistered_blocks() {
        const all_blocks = get_all_blocks_names();

        try {
            const result = await wp.apiFetch( {
                path: vipContentApi.apiRoute + "/check-blocks-registry-status",
                method: 'POST',
                data: {
                    blocks: all_blocks,
                }
            })

            console.log( result );

            return result.unregistered;
        } catch ( error ) {
            console.error( "Error getting unregistered blocks. ", error );
            return [];
        }
    }

    async function api_register_client_side_blocks( blocks ) {
        try {
            const result = await wp.apiFetch( {
                path: vipContentApi.apiRoute + "/register-client-side-blocks",
                method: 'POST',
                data: {
                    blocks: blocks,
                }
            })

            console.log( result );

            return result;
        } catch ( error ) {
            console.error( "Error registering client-side blocks", error );
            return {};
        }
    }

    function get_block_by_name( block ) {
        return wp.blocks.getBlockType( block )
    }

    // To get all the existing blocks, wait for DOM to be loaded.
    document.addEventListener("DOMContentLoaded", async () => {
        console.log( get_all_blocks_names() );

        const unregistered = await api_check_unregistered_blocks();
        const blocksToRegister = [];
        unregistered.forEach( ( blockName ) => {
            const blockType = get_block_by_name( blockName );
            blocksToRegister.push( {
                name: blockType.name,
                meta: blockType
            } );
        })

        console.log( "Going to register blocks", blocksToRegister );
        if ( blocksToRegister.length ) {
            const registerResult = await api_register_client_side_blocks( blocksToRegister );
            console.log(registerResult);
        }
    });
})();
