document.addEventListener("DOMContentLoaded", function() {
    function baseName(string) {
        return string.substring(string.lastIndexOf('/')+1).split('?')[0];
    }
    let postID                      = document.getElementById( 'current-material-post-id' ).value,
        max_file_size               = document.getElementById( 'material-max-file-size' ).value,
        accept_file                 = document.querySelector( '.lp-material--field-upload' ).getAttribute( 'accept' ).split( ',' ),
        can_upload                  = document.getElementById( 'available-to-upload' ),
        add_btn                     = document.getElementById( 'btn-lp--add-material' ),
        group_template              = document.getElementById( 'lp-material--add-material-template' ),
        material__group_container   = document.getElementById( 'lp-material--group-container' ),
        material_tab                = document.getElementById( 'lp-material-container' ),
        material_save_btn           = document.getElementById( 'btn-lp--save-material' );

    //add material group field
    add_btn.addEventListener( 'click', function( e ) {
        let can_upload_data     = ~~this.getAttribute( 'can-upload' );
        let groups = material__group_container.querySelectorAll( '.lp-material--group' ).length;
        if ( groups >= can_upload_data ) {
            return false;
        } else {
            material__group_container.insertAdjacentHTML( 'beforeend', group_template.innerHTML );
        }
        
    } );
    //switch input when change method between "upload" and "external"
    material_tab.addEventListener( 'change', function( event ) {
        let target = event.target;
        if ( target.classList.contains( 'lp-material--field-method' ) ) {
            let method = target.value;
            let upload_field_template = document.getElementById( 'lp-material--upload-field-template' ).innerHTML,
                external_field_template = document.getElementById( 'lp-material--external-field-template' ).innerHTML;
            switch ( method ) {
                case 'upload' :
                    target.parentNode.insertAdjacentHTML( 'afterend', upload_field_template );
                    target.closest( '.lp-material--group' ).querySelector( '.lp-material--external-wrap' ).remove();
                    break;
                case 'external' :
                    target.parentNode.insertAdjacentHTML( 'afterend', external_field_template );
                    target.closest( '.lp-material--group' ).querySelector( '.lp-material--upload-wrap' ).remove();
                    break;
            }
            // console.log(target.parentNode)
        }
        if ( target.classList.contains( 'lp-material--field-upload' ) ) {
            if ( target.value && target.files.length > 0 ) {
                
                if ( ! accept_file.includes( target.files[0].type ) ) {
                    alert( "This file is not allowed! Please choose another file!" );
                    target.value = '';
                } else if ( target.files[0].size > max_file_size*1024*1024 ) {
                    alert( `This file size is greater than ${max_file_size}MB! Please choose another file!` );
                    target.value = '';
                }
                return;
            }
        }
    } );
    material_tab.addEventListener( 'click', function( event ) {
        let target = event.target;
        if ( target.classList.contains( 'lp-material--delete' ) && target.nodeName == 'BUTTON' ) {
            target.closest( '.lp-material--group' ).remove();
        }
        return false;
    } );
    //save material
    material_save_btn.addEventListener( 'click', function(event) {
        let materials = material__group_container.querySelectorAll( '.lp-material--group' );
        let material_data = [];
            
        if ( materials.length > 0 ) {
            let formData = new FormData(), send_request = true;
            formData.append( 'action', '_lp_save_materials' );
            materials.forEach( function ( ele, index ) {
                let label = ele.querySelector( '.lp-material--field-title' ).value,
                    method = ele.querySelector( '.lp-material--field-method' ).value,
                    external_field = ele.querySelector( '.lp-material--field-external-link' ),
                    upload_field = ele.querySelector( '.lp-material--field-upload' ), file, link;
                if ( ! label ) {
                    send_request = false;
                }
                switch ( method ) {
                    case 'upload' :
                        if ( upload_field.value ) {
                            file = upload_field.files[0].name;
                            link = '';
                            formData.append( 'file[]', upload_field.files[0] );
                        } else {
                            send_request = false;
                        }
                        break;
                    case 'external' :
                        link = external_field.value;
                        file = '';
                        if( ! link )
                            send_request = false;
                        break;
                }
                material_data.push( { 'label': label, 'method': method, 'file':file, 'link':link } );
            } );
            if ( !send_request ) {
                alert( 'Enter file title, choose file or enter file link!' )
            } else {
                // console.log(material_data);
                material_data = JSON.stringify( material_data );
                let url = `${lpGlobalSettings.rest}lp/v1/material/item-materials/${postID}`;
                formData.append( 'data', material_data );
                // formData.append( 'post_id', postID );
                fetch( url, {
                    method: 'POST',
                    headers: {
                                'X-WP-Nonce': lpGlobalSettings.nonce,
                            },
                    body: formData,
                } ) // wrapped
                    .then( res => res.text() )
                    .then( data => {
                        console.log( data );
                        material__group_container.innerHTML = '';
                        data = JSON.parse( data );
                        if ( data.material && data.material.length > 0 ) {
                            let delete_btn_text = document.getElementById( 'delete-material-row-text' ).value,
                                material_table = document.querySelector( '.lp-material--table' );
                            for ( let i = 0; i < data.material.length; i++ ) {
                                let row = data.material[i];
                                material_table.insertAdjacentHTML( 
                                    'beforeend',
                                    `<tr>
                                      <td>${row.data.label}</td>
                                      <td>${row.data.method}</td>
                                      <td><a href="javascript:void(0)" class="delete-material-row" data-id="${row.data.id}">${delete_btn_text}</a></td>
                                    </tr>`
                                );
                            }
                            can_upload.innerText = ~~can_upload.innerText - data.material.length;
                            add_btn.setAttribute( 'can-upload', can_upload.innerText );
                        }
                        if ( data.items && data.items.length > 0 ) {
                            for ( let i = 0; i < data.items.length; i++ ) {
                                add_btn.insertAdjacentHTML(
                                    'beforebegin',
                                    `<h3 class="notice notice-error">${data.items[i].message}</h3>`
                                );
                            }
                        }

                    } )
                    .catch( err => console.log( err ) );    
            }
        }
        // console.log( data );
    } );
    //delete material
    document.addEventListener( 'click', function (e) {
        let target = e.target;
        if ( target.classList.contains( 'delete-material-row' ) && target.nodeName == 'A' ) {
            let rowID = target.getAttribute( 'data-id' ),//material file ID
                message = document.getElementById( 'delete-material-message' ).value;//Delete message content
            if ( confirm( message ) ) {
                let url = `${lpGlobalSettings.rest}lp/v1/material/${rowID}`;
                fetch( url, {
                    method: 'DELETE',
                    headers: {
                                'X-WP-Nonce': lpGlobalSettings.nonce,
                            },
                } )
                    .then( res => res.text() )
                    .then( data => {
                        data = JSON.parse( data );
                        // console.log(data);
                        if ( data.data.delete ) {
                            target.closest( 'tr' ).remove();
                            can_upload.innerText = ~~can_upload.innerText + 1;
                            add_btn.setAttribute( 'can-upload', ~~can_upload.innerText );
                        }
                    } )
                    .catch( err => console.log( err ) );
            }
        }
    } );
});