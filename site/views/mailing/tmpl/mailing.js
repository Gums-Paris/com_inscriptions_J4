jQuery(document).ready(function ($) {

    var erreur = 0;

    var task = ""; // ajout
    var email = ""; // adresse mail à ajouter
    var supp = 0; // id adresse à supprimer
    var id_liste = 0; // id_liste de l'abonnement à ajouter
    var id_adresse = 0; // id_adresse de l'abonnement à ajouter
    var id_abonnement = 0; // id abonnement à ajouter
    var url = "";

    $(".cba").click(function (e) {
        e.preventDefault();

        res = this.id.split("_");
        id_adresse = res[1];
        id_liste = res[2];

        if (this.checked) {
            task = "mailing.abonner";
        } else {
            task = "mailing.desabonner";
        }

        ajx(this.id);
    });


    $(".adrsup").click(function (e) {

        e.preventDefault();

        if(confirm("Etes-vous sûr de vouloir supprimer cette adresse et les abonnements correspondants")) {

            res = this.id.split("_");
            id_adresse = res[1];
            task = "mailing.supprimer";
            ajx();

        }

    });

    $("#ajouter").click(function (e) {

        e.preventDefault();

        email = $("#ajout").val();

        if (!isEmail(email)) {
            alert("Adresse " + email + " non valide");
        } else {
            task = "mailing.ajouter";
            ajx();
        }

    });

    function ajx(cb_id) {

        userid = $("#userid").val();
        url = $("#url").val();

        $("#message").html("Traitement en cours...");                  

        $.ajax({
            type: "GET",
            url: "index.php",
            data: {
                'option': 'com_inscriptions',
                'view': 'mailing',
                'controller': 'mailing',
                'layout': 'ajax',
                'userid': userid,
                'task': task,
                'email': email,
                'supp': supp,
                'id_adresse': id_adresse,
                'id_abonnement': id_abonnement,
                'id_liste': id_liste
            },
            success: function(data,status,xhr) {                   

                erreur = data.search("Erreur");   

                if (erreur != -1) {

                    $("#message").removeClass().addClass("alert-error");                      
                    $("#message").html(data);                  

                } else {

                    $("#message").removeClass().addClass("alert-success");                      

                    if (task == "mailing.abonner") {
                        $("#"+cb_id).attr('checked', true);
                    } 
                    if (task == "mailing.desabonner") {
                        $("#"+cb_id).attr('checked', false);
                    }                  

                    $("#message").html(data);                  

                    if (task == "mailing.ajouter" || task == "mailing.supprimer" ) {
                    setTimeout(function(){location.href=url} , 2000);                  
                    }

                }
                
            },

            error:  function(data,status,xhr) {            

                $("#message").removeClass().addClass("alert-error");                      
                $("#message").html("Erreur de traitement");                  

                console.log(data);
                console.log(status);
                console.log(xhr);

            }
        });


    }


    function isEmail(email) {
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
    }


});