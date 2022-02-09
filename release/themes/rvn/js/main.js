function search_block(url_path) {
	var input_search_block = $('#input_search_block').val();
	if (!input_search_block) {
		return;
	}
	// console.log(url_path);
	location.href = url_path + input_search_block;
}

function pageOnLoad() {
	$('#input_search_block').keypress(function (e) {
		var key = e.which;
		if (key == 13)  // the enter key code
		{
			$('#btnSearch').click();
			return false;
		}
	});

	if ($('#tblAddrTx').length) {
		$('#tblAddrTx').DataTable();
    }
}

let iframe = document.querySelector("#ipfsIFrame");

window.addEventListener('load', function () {
	let message = { height: document.body.scrollHeight, width: document.body.scrollWidth };

	// window.top refers to parent window
	window.top.postMessage(message, "*");
});

window.addEventListener('message', function (e) {
	// message that was passed from iframe page
	let message = e.data;

	if (!iframe)
		return;

	iframe.style.height = message.height * 1.2 + 'px';
	iframe.style.width = message.width / 3 + 'px';
}, false);