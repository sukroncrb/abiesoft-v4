function el(x) {
    return document.querySelector(x);
}

const app = {
    state: {

        /*


            ---------------------------------------------------------------
            Default App Opsi
            ---------------------------------------------------------------
        */
        mode: el('#app').dataset.mode,
        baseurl: el('#app').dataset.baseurl,
        token: el('#app').dataset.token,
        output: el('#app').dataset.output,
        page: el('#app').dataset.page,
        toasts: [],
    },

    showLogoutModal() {
        let container = el('#modal-container');
        modalHtml = `
            <div id="logoutModal" class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[999] flex items-center justify-center opacity-0 transition-opacity duration-300 p-4">
                <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6 transform scale-95 transition-transform duration-300">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-600 flex-shrink-0 mt-1">
                            <i data-lucide="log-out" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Konfirmasi Keluar</h3>
                            <p class="text-sm text-slate-500 mt-1 leading-relaxed">Apakah Anda yakin ingin keluar dari sesi ini? Pastikan data proyek telah disimpan.</p>
                        </div>
                    </div>
                    <div class="flex flex-col-reverse sm:flex-row gap-3 mt-6 sm:mt-8 sm:justify-end">
                        <button onclick="app.closeModal()" class="w-full sm:w-auto px-4 py-2.5 rounded-lg text-slate-600 font-medium hover:bg-slate-100 transition-colors border border-slate-200 sm:border-transparent">
                            Batal
                        </button>
                        <button onclick="app.confirmLogout()" class="w-full sm:w-auto px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium shadow-sm shadow-red-600/20 transition-all active:scale-95">
                            Ya, Keluar
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.innerHTML = modalHtml;
        this.setToShowDialogModalLogout();
        lucide.createIcons();
    },

    setToShowDialogModalLogout(){
        let logoutModal = document.getElementById('logoutModal');
        logoutModal.classList.remove('hidden');
        setTimeout(() => {
            logoutModal.classList.remove('opacity-0');
            logoutModal.querySelector('div').classList.remove('scale-95');
            logoutModal.querySelector('div').classList.add('scale-100');
        }, 10);
    },

    confirmLogout() {
        getData("logout").then(result => {
            if(result.code == 200){
                this.showToast("Menghapus sesi ..", "info");
                setTimeout(()=>{
                    this.showToast("Logout berhasil", "success");
                    window.location.href = this.state.baseurl;
                });
            }else{
                this.showToast(result.message, "error");
                return false;
            }
        });
    },

    closeModal() {
        let container = el('#modal-container');
        modalHtml = ``;
        container.innerHTML = modalHtml;
    },


    /*


        ---------------------------------------------------------------
        Toast Function
        ---------------------------------------------------------------
    */

    showToast(message, type = 'success') {
        if (this.toastTimer) {
            clearTimeout(this.toastTimer);
        }
        const id = Date.now();
        this.state.toasts = [{ id, message, type }];
        this.renderToasts();
        this.toastTimer = setTimeout(() => {
            this.removeToast(id);
        }, 3000);
    },

    removeToast(id) {
        const toastEl = document.getElementById(`toast-${id}`);
        if (toastEl) {
            setTimeout(() => {
                this.state.toasts = this.state.toasts.filter(t => t.id !== id);
                this.renderToasts();
            }, 300);
        } else {
            this.state.toasts = this.state.toasts.filter(t => t.id !== id);
            this.renderToasts();
        }
    },

    renderToasts() {
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) return;

        let html = '';
        this.state.toasts.forEach(toast => {
            let textColor = "";
            let iconName = "";

            if(toast.type == "error"){
                textColor = "text-red-500";
                iconName = "alert-octagon";
            }else if(toast.type == "info"){
                textColor = "text-slate-500";
                iconName = "info";
            } else{
                textColor = "text-green-500";
                iconName = "check-circle-2";
            }

            html += `
                <div id="toast-${toast.id}" class="w-full max-w-xs bg-white border border-slate-200 text-slate-700 px-4 py-3 rounded-xl shadow-xl flex items-start gap-3 transform transition-all duration-300 pointer-events-auto">
                    <div class="rounded-full bg-slate-50 p-1 flex-shrink-0 mt-0.5">
                        <i data-lucide="${iconName}" class="w-5 h-5 ${textColor}"></i>
                    </div>
                    <span class="text-sm font-medium pr-2 flex-1 pt-1">${toast.message}</span>
                    <button onclick="app.removeToast(${toast.id})" class="text-slate-400 hover:text-slate-600 flex-shrink-0 transition-colors mt-1">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            `;
        });
        toastContainer.innerHTML = html;
        lucide.createIcons();
    },



    /*


        ---------------------------------------------------------------
        Captcha
        ---------------------------------------------------------------
    */
    generateCaptcha() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        let captcha = '';
        
        for (let i = 0; i < 6; i++) {
            captcha += chars.charAt(Math.floor(Math.random() * chars.length));
        }

        if(el('#captchaView')){
            el('#captchaView').innerHTML = captcha;
        }

        if(el('#captcha')){
            el('#captcha').value = "";
        }
    },

    refreshCaptcha() {
        this.generateCaptcha();
    },









    /*


        ---------------------------------------------------------------
        Helper Function
        ---------------------------------------------------------------
    */

    formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    },

    isExpire(inputDateString) {
        const now = new Date();
        const inputDate = new Date(inputDateString);
        if (inputDate.getTime() < now.getTime()) {
            return true;
        } else {
            return false;
        }
    },




    /*


        ---------------------------------------------------------------
        Helper Function
        ---------------------------------------------------------------
    */

    toSubmit(form){
        form.addEventListener('submit', (e)=>{
            e.preventDefault();
            if(form.dataset.function){
                if(typeof window[form.dataset.function] === "function"){
                    let formdata = new FormData(form);
                    formdata.append('fid',form.id);
                    if(form.dataset.id){
                        formdata.append('id',form.dataset.id);
                    }
                    if(form.dataset.uuid){
                        formdata.append('uuid',form.dataset.uuid);
                    }
                    let forVerify = form.querySelectorAll('[data-validasi]');
                    for(let i=0; i<forVerify.length; i++){
                        
                        if(typeof this[forVerify[i].dataset.validasi] === "function"){
                            let validasi = this[forVerify[i].dataset.validasi](forVerify[i]);
                            if(validasi != ""){
                                this.showToast(validasi,"error");
                                forVerify[i].focus();
                                return false;
                            }
                        }
                        
                    }
                    window[form.dataset.function](formdata, form);
                }
            }
        });
    },

    



    /*


        ---------------------------------------------------------------
        Validasi
        ---------------------------------------------------------------
    */

    setParagrap(el) {
        let value = el.value;
        let min = 4;
        let label = el.dataset.label;
        let status = "";

        if(el.dataset.min){
            min = el.dataset.min;
        }

        if(value == "" || value == null || value == "<div><br></div>" || value == "<br>"  || value == "<br><div><br></div>" || value == "<div><br></div><div><br></div>"){
            status = label + " jangan dikosongkan";
        }

        else if(value.length < min ){
            status = label + " minimal "+min+" karakter.";
        }

        return status;
    },

    setText(el) {
        let value = el.value;
        let min = 1;
        let max = 255;
        let label = el.dataset.label;
        let status = "";

        if(el.dataset.min){
            min = el.dataset.min;
        }

        if(el.dataset.max){
            max = el.dataset.max;
        }

        if(value == "" || value == null || value == "<div><br></div>" || value == "<br>"  || value == "<br><div><br></div>" || value == "<div><br></div><div><br></div>"){
            status = label + " jangan dikosongkan";
        }

        else if(value.length < min ){
            status = label + " minimal "+min+" karakter.";
        }
        
        else if(value.length > max){
            status = label + " maximal "+max+" karakter.";
        }

        return status;
    },

    setKonfirmasi(el) {
        let value = el.value;
        let label = el.dataset.label;
        let konfirmasi = document.getElementById(el.dataset.konfirmasi).value;
        let status = "";

        if(value != konfirmasi){
            status = label + " tidak cocok.";
        }

        return status;
    },

    setCaptcha(el) {
        let value = el.value;
        let label = el.dataset.label;
        let captcha = document.getElementById(el.dataset.captcha).innerText;
        let status = "";

        if(value != captcha){
            status = label + " tidak cocok.";
            el.value = "";
            this.refreshCaptcha();
        }

        return status;
    },

    setPilihan(el) {
        let value = el.value;
        let label = el.dataset.label;
        let status = "";

        if(value == "" || value == null){
            status = label + " belum dipilih";
        }

        return status;
    },

    setEmail(el) {
        let value = el.value;
        let label = el.dataset.label;
        let status = "";
        
        // Regex untuk validasi format email sederhana
        const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        
        if (value === "" || value === null) {
            status = label + " jangan dikosongkan.";
        }
        
        else if (!emailRegex.test(value)) {
            status = label + " tidak valid. Contoh: nama@domain.com";
        }
        
        return status;
    },

    setUrl(el) {
        let value = el.value;
        let label = el.dataset.label;
        let status = "";
        
        // Regex untuk validasi format URL
        const urlRegex = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/;
        
        if (value === "" || value === null) {
            status = label + " jangan dikosongkan.";
        }
        
        else if (!urlRegex.test(value)) {
            status = label + " tidak valid. Contoh: http://www.example.com";
        }
        
        return status;
    },

    setAngka(el) {
        let value = el.value;
        let min = 0;
        let max = 999999999999999;
        let label = el.dataset.label;
        let status = "";
        let numberValue = parseInt(value);
        
        if (el.dataset.min) {
            min = parseInt(el.dataset.min);
        }
        
        if (el.dataset.max) {
            max = parseInt(el.dataset.max);
        }
        
        if (value === "" || value === null) {
            status = label + " jangan dikosongkan.";
        }
        
        // Cek apakah nilai hanya terdiri dari angka
        else if (isNaN(value) || !/^\d+$/.test(value)) {
            status = label + " harus berupa angka.";
        }
        
        else if (numberValue < min) {
            status = label + " minimal " + min + ".";
        }
        
        else if (numberValue > max) {
            status = label + " maksimal " + max + ".";
        }
        
        return status;
    },

    setKtp(el) {
        let value = el.value;
        let label = el.dataset.label;
        let status = "";
        
        if (value === "" || value === null) {
            status = label + " jangan dikosongkan.";
        }
        
        // Memastikan panjang KTP tepat 16 digit
        else if (value.length !== 16) {
            status = label + " harus 16 digit.";
        }
        
        // Memastikan nilai hanya terdiri dari angka
        else if (isNaN(value) || !/^\d+$/.test(value)) {
            status = label + " harus berupa angka.";
        }
        
        return status;
    },







    render() {
        this.generateCaptcha();
        lucide.createIcons();
    }

};


/*


    ---------------------------------------------------------------
    Api Function
    ---------------------------------------------------------------
*/

async function getData(endpoint) {
    try {
        
        perbaruiToken();
        
        const response = await fetch(app.state.baseurl + "api/" + endpoint, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${app.state.token}`
            }
        });
        
        let result = "";
        if (app.state.mode === "develope") {
            if(app.state.output == "json"){
                result = await response.json();
            }else{
                result = await response.text();
            }
        }else{
            result = await response.json();
        }
        
        if(app.state.mode == "develope"){
            console.log(result);
        }
        return result; 
    } catch (error) {
        if(app.state.mode == "develope"){
            console.error("Error fetching data pada enpoint: ", endpoint);
        }
    }
}

async function getBigData(endpoint) {
    let allData = [];
    let offset = 0;
    const limit = 100;
    let keepFetching = true;
    if(app.state.mode == "develope"){
        console.log("Memulai penarikan data besar...");
    }
    while (keepFetching) {
        const pagedEndpoint = `${endpoint}/${offset}/${limit}`;
        try {
            const result = await getData(pagedEndpoint);
            const dataChunk = result.data || [];
            if (Array.isArray(dataChunk) && dataChunk.length > 0) {
                allData = allData.concat(dataChunk);
                if(app.state.mode == "develope"){
                    console.log(`Berhasil mengambil ${allData.length} data...`);
                }
                offset += limit;
            } else {
                keepFetching = false;
                if(app.state.mode == "develope"){
                    console.log("Semua data telah diambil.");
                }
            }
        } catch (error) {
            if(app.state.mode == "develope"){
                console.error("Gagal mengambil batch data pada offset: ", offset);
            }
            keepFetching = false; 
        }
    }

    return allData;
}

async function postData(formdata, endpoint, form, labelCustom = "Menyimpan") {
    let btnSubmit = "";
    let labelButton = "";
    try {
        let loaderBtn = '<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div> '+labelCustom+'...';
        if(form){
            
            perbaruiToken();
        
            if(form.querySelector('[type="submit"]')){
                btnSubmit = form.querySelector('[type="submit"]');
                labelButton = btnSubmit.innerHTML;
                btnSubmit.innerHTML = loaderBtn;
                btnSubmit.setAttribute('disabled', true);
            }
        }
        const response = await fetch(app.state.baseurl + "api/" + endpoint, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${app.state.token}`
            },
            body: formdata
        });

        let result = "";
        if (app.state.mode === "develope") {
            if(app.state.output == "json"){
                result = await response.json();
            }else{
                result = await response.text();
            }
        }else{
            result = await response.json();
        }

        if(btnSubmit){
            btnSubmit.innerHTML = labelButton;
            btnSubmit.removeAttribute('disabled');
        }
        if(result.code != 200){
            app.refreshCaptcha();
        }
        if (app.state.mode === "develope") {
            console.log(result);
        }
        return result;
    } catch (error) {
        if(btnSubmit){
            btnSubmit.innerHTML = labelButton;
            btnSubmit.removeAttribute('disabled');
        }
        if (app.state.mode === "develope") {
            console.error("Error fetching data pada enpoint: ", error);
        }
    }
}

async function postDelete(form) {
    try {
        let tabel = "";
        let label = "";
        let next = "";
        let id = "";
        let uuid = "";
        if(form.dataset.tabel){
            tabel = form.dataset.tabel;
        }
        if(form.dataset.label){
            label = form.dataset.label;
        }
        if(form.dataset.next){
            next = form.dataset.next;
        }
        let formdata = new FormData();
        formdata.append("fid", form.id);
        formdata.append("__csrf", form.dataset.csrf);
        formdata.append("__method", "DELETE");
        if(form.dataset.id){
            id = form.dataset.id;
            formdata.append("id", id);
        }
        if(form.dataset.uuid){
            uuid = form.dataset.uuid;
            formdata.append("uuid", uuid);
        }

        perbaruiToken();

        const response = await fetch(app.state.baseurl + "api/" + tabel, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${app.state.token}`
            },
            body: formdata
        });
        
        let result = "";
        if (app.state.mode === "develope") {
            if(app.state.output == "json"){
                result = await response.json();
            }else{
                result = await response.text();
            }
        }else{
            result = await response.json();
        }

        if (app.state.mode === "develope") {
            console.log(result);
        }
        
        if(result.code == 200){
            app.showToast("Data "+label+ " dihapus.");
            if(next.includes("fc:")){
                let namaFunction = next.replace("fc:","");
                if (typeof window[namaFunction] === "function") {
                    window[namaFunction]();
                }
            }else{
                setTimeout(()=>{
                    window.location.href = app.state.baseurl + next;
                }, 1000);
            }
        }else{
            app.showToast(result.message, "error");
            return false;
        }
    } catch (error) {
        if (app.state.mode === "develope") {
            console.error("Error fetching data pada enpoint: ", error);
        }
    }
}

let refreshTokenTerakhir = Date.now();
function perbaruiToken() {
    const sekarang = Date.now();

    const sepuluhMenit = 10 * 60 * 1000;

    if (sekarang - refreshTokenTerakhir > sepuluhMenit) {
        fetch(app.state.baseurl + "api/auth/refresh-token", {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${app.state.token}`
            }
        })
        .then(response => response.json())
        .then(result => {
            if (app.state.mode === "develope") {
                console.error(result);
            }
            if (result.code == 200) {
                console.log(result);
                document.getElementById('app').dataset.token = result.data;
                app.state.token = result.data;
                refreshTokenTerakhir = sekarang;
            }else{
                if (app.state.mode === "develope") {
                    console.error("Gagal refresh Bearer");
                }
            }
        });
    }
}


/*


    ---------------------------------------------------------------
    CSRF
    ---------------------------------------------------------------
*/

function csrfToken(form, fc) {
    if(form){
        let forms = document.querySelector('[data-form="'+form+'"]');
        getData('csrf/'+forms.dataset.form).then(result => {
            if(forms.dataset.form.includes("hapus")){
                forms.setAttribute('id', result.data.fid);
                forms.setAttribute('data-csrf', result.data.csrf);
                localStorage.setItem(result.data.fid, result.data.csrf);
                forms.addEventListener('click', ()=>{
                    buttonHapus(forms);
                });
            }else if(forms.dataset.form.includes("post")){
                forms.setAttribute('id', result.data.fid);
                forms.setAttribute('data-csrf', result.data.csrf);
                localStorage.setItem(result.data.fid, result.data.csrf);
            }else{
                forms.setAttribute('id', result.data.fid);
                let csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '__csrf';
                csrfInput.value = result.data.csrf;
                localStorage.setItem(result.data.fid, result.data.csrf);
                forms.insertBefore(csrfInput, forms.children[forms.children.length - 1]); // Menambahkan sebelum tombol submit
            }
            if(typeof window[fc] === "function"){
                window[fc]();
            }
        });
    }else{
        let forms = document.querySelectorAll('[data-form]');
        if(forms.length > 0){
            for(let i=0; i<forms.length; i++){
                getData('csrf/'+forms[i].dataset.form).then(result => {
                    if(forms[i].dataset.form.includes("hapus")){
                        forms[i].setAttribute('id', result.data.fid);
                        forms[i].setAttribute('data-csrf', result.data.csrf);
                        localStorage.setItem(result.data.fid, result.data.csrf);
                        forms[i].addEventListener('click', ()=>{
                            postDelete(forms[i]);
                        });
                    }else if(forms[i].dataset.form.includes("post")){
                        forms[i].setAttribute('id', result.data.fid);
                        forms[i].setAttribute('data-csrf', result.data.csrf);
                        localStorage.setItem(result.data.fid, result.data.csrf);
                        app.toSubmit(forms[i]);
                    }else{
                        forms[i].setAttribute('id', result.data.fid);
                        let csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '__csrf';
                        csrfInput.value = result.data.csrf;
                        localStorage.setItem(result.data.fid, result.data.csrf);
                        forms[i].insertBefore(csrfInput, forms[i].children[forms[i].children.length - 1]); // Menambahkan sebelum tombol submit
                        app.toSubmit(forms[i]);
                    }
                });
            }
        }
    }
}

csrfToken();

async function cekRefreshToken() {
    const forms = document.querySelectorAll("[data-form]");
    if (!forms.length) return;

    const requests = Array.from(forms).map(async (form) => {
        const csrfInput = form.querySelector('input[name="__csrf"]');
        if (!csrfInput || !form.id) return;

        try {
            const result = await getData("token/" + form.id);
            if (result.code == 200 && csrfInput.value !== result.data) {
                csrfInput.value = result.data;
            }
        } catch (e) {
            console.error("Sync CSRF gagal untuk " + form.id);
        }
    });

    await Promise.all(requests);
}

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        cekRefreshToken();
    }
});


window.togglePassword = function(inputId, btnId) {
    const input = document.getElementById(inputId);
    const btn = document.getElementById(btnId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<i data-lucide="eye-off" class="w-5 h-5 text-blue-600"></i>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<i data-lucide="eye" class="w-5 h-5 text-gray-400"></i>';
    }
    lucide.createIcons();
}

/*


    ---------------------------------------------------------------
    Button data link
    ---------------------------------------------------------------
*/
lucide.createIcons();

let dataLink = document.querySelectorAll("[data-link]");
if(dataLink){
    for(let i=0; i<dataLink.length; i++){
        dataLink[i].setAttribute("type","button");
        dataLink[i].addEventListener('click', ()=>{
            window.location.href = dataLink[i].dataset.link;
        });
    }
}

if(document.getElementById('currentDay')){
    const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const today = new Date().toLocaleDateString('id-ID', dateOptions).split(', ');
    document.getElementById('currentDay').textContent = today[0];
    document.getElementById('currentDate').textContent = today[1];
}

const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

function toggleSidebar() {
    sidebar.classList.toggle('-translate-x-full');
    if (sidebar.classList.contains('-translate-x-full')) {
        overlay.classList.add('opacity-0');
        setTimeout(() => overlay.classList.add('hidden'), 300);
    } else {
        overlay.classList.remove('hidden');
        setTimeout(() => overlay.classList.remove('opacity-0'), 10);
    }
}

const notifDropdown = document.getElementById('notifDropdown');
const bellBtn = document.getElementById('bellBtn');
const profileDropdown = document.getElementById('profileDropdown');
const profileBtn = document.getElementById('profileBtn');

function toggleNotifications(event) {
    event.stopPropagation();
    if(notifDropdown.classList.contains('hidden')) {
        profileDropdown.classList.add('hidden');
        notifDropdown.classList.remove('hidden');
        setTimeout(() => notifDropdown.classList.remove('opacity-0', 'scale-95'), 10);
    } else {
        notifDropdown.classList.add('hidden');
    }
}

function toggleProfileDropdown(event) {
    event.stopPropagation();
    if(profileDropdown.classList.contains('hidden')) {
        notifDropdown.classList.add('hidden');
        profileDropdown.classList.remove('hidden');
        setTimeout(() => profileDropdown.classList.remove('opacity-0', 'scale-95'), 10);
    } else {
        profileDropdown.classList.add('hidden');
    }
}

// Tutup dropdown jika klik di luar
window.addEventListener('click', function(e) {
    if(notifDropdown){
        if (!notifDropdown.contains(e.target) && !bellBtn.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }
        if (!profileDropdown.contains(e.target) && !profileBtn.contains(e.target)) {
            profileDropdown.classList.add('hidden');
        }
    }
});

/* --- MODAL LOGOUT LOGIC --- */
const logoutModal = document.getElementById('logoutModal');

function showLogoutModal() {
    profileDropdown.classList.add('hidden');
    logoutModal.classList.remove('hidden');
    setTimeout(() => {
        logoutModal.classList.remove('opacity-0');
        logoutModal.querySelector('div').classList.remove('scale-95');
        logoutModal.querySelector('div').classList.add('scale-100');
    }, 10);
}

function hideLogoutModal() {
    logoutModal.classList.add('opacity-0');
    logoutModal.querySelector('div').classList.remove('scale-100');
    logoutModal.querySelector('div').classList.add('scale-95');
    setTimeout(() => {
        logoutModal.classList.add('hidden');
    }, 300);
}

function confirmLogout() {
    getData("logout").then(result => {
        if(result.code == 200){
            this.showToast("Menghapus sesi ..", "info");
            hideLogoutModal();
            setTimeout(()=>{
                this.showToast("Logout berhasil", "success");
                window.location.href = this.state.baseurl;
            });
        }else{
            this.showToast(result.message, "error");
            return false;
        }
    });
}