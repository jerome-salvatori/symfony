function handleModal() {
    $('#search').click(function() {
        $('#btn-modal-search').click();
    });
    
    $('#confirm-search').click(function() {
        $('#frm-rech').submit();
    });
}