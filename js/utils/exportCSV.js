// Utility to export array of objects to CSV and trigger download using Blob + createObjectURL
(function(window){
    function escapeCSV(value) {
        if (value === null || value === undefined) return '';
        const s = String(value);
        // Escape double quotes by doubling them
        const escaped = s.replace(/"/g, '""');
        // Wrap in double quotes if contains comma, newline or double quote
        if (/[",\n\r]/.test(s)) {
            return '"' + escaped + '"';
        }
        return escaped;
    }

    function objectArrayToCSV(rows, columns) {
        if (!Array.isArray(rows) || rows.length === 0) return '';

        const keys = columns && columns.length ? columns : Object.keys(rows[0]);
        const header = keys.map(k => escapeCSV(k)).join(';');
        const lines = rows.map(r => keys.map(k => escapeCSV(r[k] ?? '')).join(';'));
        return header + '\n' + lines.join('\n');
    }

    async function exportArrayToCSV(rows, filename, columns) {
        try {
            const csv = objectArrayToCSV(rows, columns);
            // Prepend BOM to help Excel open UTF-8 correctly
            const bom = new Uint8Array([0xEF, 0xBB, 0xBF]);
            const blob = new Blob([bom, csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);

            // Revoke after a short delay to ensure download started in all browsers
            setTimeout(() => URL.revokeObjectURL(url), 1000);
            return true;
        } catch (err) {
            console.error('Export CSV error', err);
            throw err;
        }
    }

    async function fetchAndExport(url, filename, columns) {
        const res = await fetch(url, { credentials: 'same-origin' });
        if (!res.ok) throw new Error('Error fetching data: ' + res.status);
        const data = await res.json();
        // if data is an object with error
        if (!Array.isArray(data)) {
            throw new Error('Respuesta inválida del servidor');
        }
        return exportArrayToCSV(data, filename, columns);
    }

    window.TrackifyExportCSV = {
        exportArrayToCSV,
        fetchAndExport
    };

})(window);