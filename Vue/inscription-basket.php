
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Basket | Tournoi</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<header class="header">
    <h1>Inscription au tournoi de basket</h1>
    <p>Crée ton équipe ou rejoins une équipe existante.</p>
</header>

<main class="container">

    <a class="back" href="sports.php?name=basketball">
        &larr; Retour au tournoi
    </a>

    <section class="section">
        <h2>Compte de démonstration</h2>

        <label for="compte">Connecté en tant que :</label>
        <select id="compte" class="form-control">
            <option value="lucas">Lucas — Capitaine</option>
            <option value="emma">Emma — Joueuse</option>
            <option value="noah">Noah — Joueur</option>
        </select>

        <p id="profilActuel" class="description"></p>
    
    </section>

    <section id="sectionCreation" class="section">
        <h2>Créer une équipe</h2>

        <form id="formEquipe" class="form-panel">
            <label for="nomEquipe">Nom de l'équipe</label>
            <input
                type="text"
                id="nomEquipe"
                class="form-control"
                placeholder="Ex. Les Avignonnais"
                maxlength="50"
                required
            >

            <button type="submit" class="btn-register">
                Créer mon équipe
            </button>
        </form>
    </section>

    <section class="section">
        <h2>Équipes disponibles</h2>
        <div id="listeEquipes"></div>
    </section>

    <section id="sectionDemandes" class="section">
        <h2>Demandes reçues par mes équipes</h2>
        <div id="demandesRecues"></div>
    </section>

    <section class="section">
        <h2>Mes demandes</h2>
        <div id="mesDemandes"></div>
    </section>

</main>

<footer class="footer">
    Démonstration du tournoi de basket — Projet étudiant
</footer>

<script>
const COMPTES = {
    lucas: {
        nom: "Lucas",
        role: "capitaine"
    },
    emma: {
        nom: "Emma",
        role: "joueur"
    },
    noah: {
        nom: "Noah",
        role: "joueur"
    }
};

const CLE_EQUIPES = "basket_demo_equipes_v2";
const CLE_COMPTE = "basket_demo_compte_v2";

let compteActuel = localStorage.getItem(CLE_COMPTE) || "lucas";

function chargerEquipes() {
    try {
        const donnees = JSON.parse(localStorage.getItem(CLE_EQUIPES));
        return Array.isArray(donnees) ? donnees : [];
    } catch {
        return [];
    }
}

function sauvegarderEquipes(equipes) {
    localStorage.setItem(CLE_EQUIPES, JSON.stringify(equipes));
}

function echapper(texte) {
    return String(texte).replace(/[&<>"']/g, caractere => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
    })[caractere]);
}

function nomCompte(id) {
    return COMPTES[id]?.nom || "Compte inconnu";
}

function statutTexte(statut) {
    return {
        attente: "En attente",
        acceptee: "Acceptée",
        refusee: "Refusée"
    }[statut] || statut;
}

function afficher() {
    const compte = COMPTES[compteActuel];
    const equipes = chargerEquipes();

    document.getElementById("compte").value = compteActuel;

 
    document.getElementById("sectionCreation").hidden =
        compte.role !== "capitaine";

    document.getElementById("sectionDemandes").hidden =
        compte.role !== "capitaine";

    afficherEquipes(equipes);
    afficherDemandesRecues(equipes);
    afficherMesDemandes(equipes);
}

function afficherEquipes(equipes) {
    const conteneur = document.getElementById("listeEquipes");

    if (equipes.length === 0) {
        conteneur.innerHTML = `
            <div class="empty">
                Aucune équipe pour le moment.
                Connecte-toi en tant que Lucas pour créer la première équipe.
            </div>`;
        return;
    }

    conteneur.innerHTML = equipes.map(equipe => {
        const maDemande = equipe.demandes.find(
            demande => demande.compte === compteActuel
        );

        const estCapitaine = equipe.capitaine === compteActuel;
        const membres = equipe.demandes.filter(
            demande => demande.statut === "acceptee"
        );

        let action = "";

        if (estCapitaine) {
            action = `<p class="info-message">Tu es le capitaine de cette équipe.</p>`;
        } else if (maDemande?.statut === "attente") {
            action = `<p class="status-pending">Demande en attente de réponse.</p>`;
        } else if (maDemande?.statut === "acceptee") {
            action = `<p class="status">Ta demande a été acceptée !</p>`;
        } else {
            action = `
                <button
                    type="button"
                    class="btn-register"
                    data-action="demander"
                    data-equipe="${echapper(equipe.id)}">
                    ${maDemande?.statut === "refusee"
                        ? "Envoyer une nouvelle demande"
                        : "Demander à rejoindre"}
                </button>`;
        }

        const listeMembres = membres.length
            ? membres.map(membre => echapper(nomCompte(membre.compte))).join(", ")
            : "Aucun membre accepté pour le moment";

        return `
            <article class="team-card">
                <div class="team-card-content">
                    <h3>${echapper(equipe.nom)}</h3>
                    <p class="description">
                        Capitaine : ${echapper(nomCompte(equipe.capitaine))}
                    </p>
                    <p class="team-members">
                        <strong>Membres acceptés :</strong> ${listeMembres}
                    </p>
                    ${action}
                </div>
            </article>`;
    }).join("");
}

function afficherDemandesRecues(equipes) {
    const conteneur = document.getElementById("demandesRecues");

    if (COMPTES[compteActuel].role !== "capitaine") {
        conteneur.innerHTML = "";
        return;
    }

    const demandes = equipes.flatMap(equipe =>
        equipe.capitaine === compteActuel
            ? equipe.demandes
                .filter(demande => demande.statut === "attente")
                .map(demande => ({ equipe, demande }))
            : []
    );

    if (demandes.length === 0) {
        conteneur.innerHTML = `
            <div class="empty">
                Tu n'as aucune demande en attente.
            </div>`;
        return;
    }

    conteneur.innerHTML = demandes.map(({ equipe, demande }) => `
        <article class="team-card">
            <div class="team-card-content">
                <h3>${echapper(equipe.nom)}</h3>
                <p>
                    <strong>Demande de :</strong>
                    ${echapper(nomCompte(demande.compte))}
                </p>

                <div class="button-row">
                    <button
                        type="button"
                        class="btn-register"
                        data-action="accepter"
                        data-equipe="${echapper(equipe.id)}"
                        data-joueur="${echapper(demande.compte)}">
                        Accepter
                    </button>

                    <button
                        type="button"
                        class="btn-secondary"
                        data-action="refuser"
                        data-equipe="${echapper(equipe.id)}"
                        data-joueur="${echapper(demande.compte)}">
                        Refuser
                    </button>
                </div>
            </div>
        </article>
    `).join("");
}

function afficherMesDemandes(equipes) {
    const conteneur = document.getElementById("mesDemandes");

    const demandes = equipes.flatMap(equipe =>
        equipe.demandes
            .filter(demande => demande.compte === compteActuel)
            .map(demande => ({ equipe, demande }))
    );

    if (demandes.length === 0) {
        conteneur.innerHTML = `
            <div class="empty">
                Tu n'as envoyé aucune demande.
            </div>`;
        return;
    }

    conteneur.innerHTML = demandes.map(({ equipe, demande }) => `
        <article class="team-card">
            <div class="team-card-content">
                <h3>${echapper(equipe.nom)}</h3>
                <p>
                    Statut :
                    <strong>${echapper(statutTexte(demande.statut))}</strong>
                </p>
            </div>
        </article>
    `).join("");
}

// Changer de compte sans effacer les équipes.
document.getElementById("compte").addEventListener("change", function () {
    compteActuel = this.value;
    localStorage.setItem(CLE_COMPTE, compteActuel);
    afficher();
});

// Créer une équipe avec le compte capitaine.
document.getElementById("formEquipe").addEventListener("submit", function (event) {
    event.preventDefault();

    if (COMPTES[compteActuel].role !== "capitaine") {
        alert("Seul un capitaine peut créer une équipe.");
        return;
    }

    const nom = document.getElementById("nomEquipe").value.trim();

    if (!nom) return;

    const equipes = chargerEquipes();

    equipes.push({
        id: "equipe_" + Date.now() + "_" +
            Math.random().toString(36).slice(2, 8),
        nom,
        capitaine: compteActuel,
        demandes: []
    });

    sauvegarderEquipes(equipes);
    this.reset();
    afficher();
});

// Gérer les boutons des équipes et les demandes.
document.addEventListener("click", function (event) {
    const bouton = event.target.closest("button[data-action]");
    if (!bouton) return;

    const action = bouton.dataset.action;
    const idEquipe = bouton.dataset.equipe;
    const equipes = chargerEquipes();
    const equipe = equipes.find(e => e.id === idEquipe);

    if (!equipe) return;

    if (action === "demander") {
        if (COMPTES[compteActuel].role !== "joueur") {
            alert("Choisis un compte joueur pour demander à rejoindre une équipe.");
            return;
        }

        if (equipe.capitaine === compteActuel) {
            alert("Tu es déjà le capitaine de cette équipe.");
            return;
        }

        const demande = equipe.demandes.find(
            d => d.compte === compteActuel
        );

        if (demande?.statut === "acceptee") {
            alert("Tu fais déjà partie de cette équipe.");
            return;
        }

        if (demande?.statut === "attente") {
            alert("Ta demande est déjà en attente.");
            return;
        }

        if (demande) {
            demande.statut = "attente";
        } else {
            equipe.demandes.push({
                compte: compteActuel,
                statut: "attente"
            });
        }

        sauvegarderEquipes(equipes);
        afficher();
        alert("Demande envoyée au capitaine !");
        return;
    }

    // Seul le capitaine propriétaire de l'équipe peut répondre.
    if (
        COMPTES[compteActuel].role !== "capitaine" ||
        equipe.capitaine !== compteActuel
    ) {
        alert("Seul le capitaine de cette équipe peut répondre.");
        return;
    }

    const idJoueur = bouton.dataset.joueur;
    const demande = equipe.demandes.find(
        d => d.compte === idJoueur && d.statut === "attente"
    );

    if (!demande) return;

    if (action === "accepter") {
        demande.statut = "acceptee";
    } else if (action === "refuser") {
        demande.statut = "refusee";
    } else {
        return;
    }

    sauvegarderEquipes(equipes);
    afficher();

    alert(action === "accepter"
        ? "Demande acceptée !"
        : "Demande refusée.");
});

afficher();
</script>

</body>
</html>
