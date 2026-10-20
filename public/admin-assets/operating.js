(() => {
    const translate = text => window.findoAdminTranslations?.[text] ?? text;
    const root = document.querySelector('[data-scorecard]');
    if (!root) return;
    const area = document.querySelector('textarea[name="data[ratings]"]');
    const role = document.querySelector('input[name="data[scorecard_role]"]');
    if (!area) return;
    const defaults = JSON.parse(root.dataset.scorecards);
    let rows;
    try { rows = JSON.parse(area.value || '[]'); } catch { return; }
    if (!Array.isArray(rows)) return;
    const table = document.createElement('table');
    const head = document.createElement('thead');
    const headingRow = document.createElement('tr');
    for (const label of ['المعيار', 'الوزن', 'الدرجة', 'الدليل', 'الإجراءات']) {
        const cell = document.createElement('th'); cell.textContent = translate(label); headingRow.append(cell);
    }
    head.append(headingRow); table.append(head);
    const body = document.createElement('tbody'); table.append(body);
    const status = document.createElement('p');
    const sync = () => {
        const entered = [...body.rows].map(row => {
            const fields = row.querySelectorAll('input');
            return {criterion: fields[0].value, weight: Number(fields[1].value), rating: Number(fields[2].value), evidence: fields[3].value};
        });
        area.value = entered.length ? JSON.stringify(entered) : '';
        const total = entered.reduce((sum, item) => sum + item.weight, 0);
        const score = entered.reduce((sum, item) => sum + item.weight * item.rating / 5, 0);
        status.textContent = `${translate('مجموع الأوزان')}: ${total}% · ${translate('الدرجة الموزونة')}: ${score.toFixed(2)} / 100`;
        status.className = total === 100 ? 'muted' : 'danger';
    };
    const render = () => {
        body.replaceChildren();
        for (const item of rows) {
            const row = document.createElement('tr');
            ['criterion','weight','rating','evidence'].forEach((key,index) => {
                const cell = document.createElement('td'); const input = document.createElement('input');
                input.type = index === 1 || index === 2 ? 'number' : 'text'; input.value = item[key] ?? '';
                input.required = true; input.disabled = area.disabled; input.setAttribute('aria-label', translate(['المعيار','الوزن','الدرجة','الدليل'][index]));
                if (index === 1 || index === 2) { input.min = 1; input.max = index === 2 ? 5 : 100; }
                input.addEventListener('input', sync); cell.append(input); row.append(cell);
            });
            const cell = document.createElement('td'); const remove = document.createElement('button');
            remove.type = 'button'; remove.className = 'text-button danger'; remove.textContent = translate('حذف'); remove.disabled = area.disabled;
            remove.addEventListener('click', () => {row.remove(); sync();}); cell.append(remove); row.append(cell); body.append(row);
        }
        sync();
    };
    const controls = document.createElement('div'); controls.className = 'row-actions';
    const selector = document.createElement('select');
    selector.setAttribute('aria-label', translate('اختر بطاقة الدور'));
    const blank = document.createElement('option'); blank.value=''; blank.textContent=translate('اختر بطاقة الدور'); selector.append(blank);
    for (const key of Object.keys(defaults)) { const option=document.createElement('option'); option.value=key; option.textContent=root.dataset[key] ?? key; selector.append(option); }
    const apply = document.createElement('button'); apply.type='button'; apply.className='button secondary'; apply.textContent=translate('تطبيق بطاقة الدور واستبدال الصفوف');
    apply.addEventListener('click', () => {
        if (selector.value) {
            rows = Object.entries(defaults[selector.value]).map(([criterion,weight]) => ({criterion,weight,rating:'',evidence:''}));
            if (role) role.value=selector.value; render();
        }
    });
    const add = document.createElement('button'); add.type='button'; add.className='button secondary'; add.textContent=translate('إضافة معيار');
    add.addEventListener('click', () => { rows=[...body.rows].map(row => {const fields=row.querySelectorAll('input');return {criterion:fields[0].value,weight:fields[1].value,rating:fields[2].value,evidence:fields[3].value};});rows.push({criterion:'',weight:'',rating:'',evidence:''});render(); });
    controls.append(selector,apply,add); root.replaceChildren(controls,table,status);
    area.closest('label').classList.add('hidden'); render();
})();

(() => {
    document.querySelectorAll('[data-line-items]').forEach((editor) => {
        const body = editor.querySelector('tbody');
        const number = (row, name) => parseFloat(row.querySelector(`[data-${name}]`)?.value) || 0;
        const recalc = () => body.querySelectorAll('[data-line]').forEach((row) => {
            const net = number(row, 'quantity') * number(row, 'unit-price') * (1 - number(row, 'discount') / 100);
            row.querySelector('[data-line-total]').textContent = row.querySelector('input')?.value.trim() ? (net * (1 + number(row, 'tax') / 100)).toFixed(2) : '—';
        });
        editor.addEventListener('input', recalc);
        editor.querySelector('[data-add-line]')?.addEventListener('click', () => {
            const row = body.querySelector('[data-line]:last-child').cloneNode(true);
            const index = editor.dataset.next++;
            row.querySelectorAll('input,select').forEach((input) => {
                input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                if (input.tagName === 'INPUT') input.value = input.hasAttribute('data-quantity') ? '1' : '';
                if (input.tagName === 'SELECT') input.value = 'service';
            });
            body.appendChild(row);
            recalc();
            row.querySelector('input')?.focus();
        });
        editor.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-remove-line]');
            if (!remove) return;
            const row = remove.closest('[data-line]');
            if (body.querySelectorAll('[data-line]').length > 1) row.remove();
            else row.querySelectorAll('input').forEach((input) => { input.value = ''; });
            recalc();
        });
        recalc();
    });
})();
(() => {
    const form = document.querySelector('[data-crm-columns-form]');
    if (!form) return;
    const key = 'findo.crm.columns';
    const params = new URLSearchParams(location.search);
    try {
        if (!params.has('columns[]')) {
            const saved = JSON.parse(localStorage.getItem(key) || 'null');
            if (Array.isArray(saved) && saved.length) {
                saved.forEach((column) => params.append('columns[]', column));
                location.replace(location.pathname + '?' + params.toString());
                return;
            }
        }
        form.addEventListener('submit', () => {
            const columns = [...form.querySelectorAll('input[name="columns[]"]:checked')].map((input) => input.value);
            localStorage.setItem(key, JSON.stringify(columns));
        });
        form.querySelector('[data-crm-columns-reset]')?.addEventListener('click', () => localStorage.removeItem(key));
    } catch (error) { /* storage blocked: columns simply fall back to the defaults */ }
})();
(() => {
    const board = document.querySelector('[data-pipeline-dnd]');
    if (!board) return;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    let dragged = null;
    board.addEventListener('dragstart', (event) => {
        dragged = event.target.closest('.pipeline-card');
        if (dragged) event.dataTransfer.setData('text/plain', dragged.dataset.id);
    });
    board.querySelectorAll('section[data-stage]').forEach((column) => {
        column.addEventListener('dragover', (event) => { if (dragged) { event.preventDefault(); column.classList.add('drop-target'); } });
        column.addEventListener('dragleave', () => column.classList.remove('drop-target'));
        column.addEventListener('drop', async (event) => {
            event.preventDefault();
            column.classList.remove('drop-target');
            const card = dragged;
            dragged = null;
            if (!card || card.closest('section') === column) return;
            const reason = prompt(board.dataset.confirm);
            if (!reason) return;
            const response = await fetch('/admin/operations/record/' + card.dataset.id + '/transition', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token},
                body: JSON.stringify({target: column.dataset.stage, version: Number(card.dataset.version), reason}),
            });
            if (response.ok) { location.reload(); return; }
            const body = await response.json().catch(() => ({}));
            alert(body.errors?.workflow?.[0] || body.errors?.target?.[0] || body.message || response.status);
        });
    });
})();
