function acciones(url,metodo,idorden, purchasenumber, merchantid, accesskey, secretkey){
	jQuery.post(url+"/wp-admin/admin-ajax.php?action=visanetAcciones&metodo="+metodo+"&ordernumber="+idorden+"&purchasenumber="+purchasenumber+"", "{}", function( data ) {

		if(data.errorCode==0){
			alert("Operación realizada correctamente.");
			location.reload();
		}else{
			alert("Ocurrió un error: "+data.errorMessage);
		}

	}, 'json');
}