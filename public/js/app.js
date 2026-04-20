function updateStatusFields(statut, company) {
    const statutInput = document.getElementById(`${statut}`).value;
    const companyFields = document.getElementById(`${company}`);
    companyFields.style.display = statutInput === 'professionnel' ? 'block' : 'none';
}

const jsonFetch = async (url) => {
    const response = await fetch(url, {
        headers: {
            'Content-Type': 'application/json',
        },
    });

    if (!response.ok) {
        const errorText = await response.text();
        throw new Error(errorText || `HTTP error ${response.status}`);
    }

    return response.json();
};

/**
 * CREATE USER
 */
const userForm = document.getElementById('userForm');

if (userForm) {
    userForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);

            const response = await fetch('/create-user', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },

                body: JSON.stringify(data)
            });
            const result = await response.json();
            addResultContent(result, 'Utilisateur créé avec succès!');
        } catch (error) {
            console.error('Erreur création utilisateur:', error.message);
        }
    });
}

/**
 * GET USER
 */
const getUserForm = document.getElementById('getUserForm');

if (getUserForm) {
    getUserForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        try {
            let formData = new FormData(getUserForm);
            let { userId } = Object.fromEntries(formData);

            const result = await jsonFetch(`/user/${userId}`, {
                method: 'GET'
            });

            addResultContent(result, 'Utilisateur récupéré avec succès!');
        } catch (error) {
            console.error('Erreur récupération utilisateur:', error.message);
        }
    });
}

/**
 * UPLOAD FILE
 */
const uploadForm = document.getElementById('uploadForm');
if (uploadForm) {
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            let formData = new FormData(uploadForm);
            const response = await fetch('/upload', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            addResultContent(result, 'Fichier uploadé avec succès!');
        } catch (error) {
            console.error('Erreur upload fichier:', error.message);
        }
    });
}

/**
 * SEND MAIL
 */
const sendMailForm = document.getElementById('sendMailForm');
if (sendMailForm) {
    sendMailForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);

            const response = await fetch('/send-mail', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            const result = await response.json();
            addResultContent(result, 'Mail envoyé avec succès!');
        } catch (error) {
            console.error('Erreur envoi mail:', error.message);
        }
    });
}

/**
 * GET MAIL
 */
const getMailForm = document.getElementById('getMailForm');

if (getMailForm) {
    getMailForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        try {
            let formData = new FormData(getMailForm);
            let { mail_id } = Object.fromEntries(formData);

            const result = await jsonFetch(`/mail/${mail_id}`, {
                method: 'GET'
            });

            addResultContent(result, 'Mail récupéré avec succès!');
        } catch (error) {
            console.error('Erreur récupération mail:', error.message);
        }
    });
}

function addResultContent(result, message) {
    resultContent.innerHTML = `
                <div class="success">✓ ${message}</div>
                <pre>${JSON.stringify(result, null, 2)}</pre>
            `;
    resultSection.style.display = 'block';
}
