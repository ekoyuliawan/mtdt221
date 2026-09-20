window.Aidofy = window.Aidofy || {};

window.Aidofy.Utils = class {
    static parseRobustJSON(str) {
        const rx = new RegExp('^```(json)?|```$', 'gi');
        let clean = str.replace(rx, '').trim();
        const start = clean.indexOf('{');
        if (start === -1) throw new Error("No JSON found.");
        clean = clean.substring(start);
        while (clean.length > 0) {
            try { return JSON.parse(clean); } 
            catch (e) {
                const lastBrace = clean.lastIndexOf('}');
                if (lastBrace <= 0) break;
                clean = clean.substring(0, lastBrace).trim();
                const newLastBrace = clean.lastIndexOf('}');
                if (newLastBrace !== -1) clean = clean.substring(0, newLastBrace + 1);
                else break;
            }
        }
        return JSON.parse(str.replace(rx, '').trim());
    }

    static compileCSVRow(res, parsed) {
        const catMap = {
            'Animals':1, 'Buildings and Architecture':2, 'Business':3, 'Drinks':4, 'The Environment':5, 'States of Mind':6, 'Food':7, 'Graphic Resources':8, 'Hobbies and Leisure':9, 'Industry':10, 'Landscape':11, 'Lifestyle':12, 'People':13, 'Plants and Flowers':14, 'Culture and Religion':15, 'Science':16, 'Social Issues':17, 'Sports':18, 'Technology':19, 'Transport':20, 'Travel':21
        };

        let row = [
            res.filename || '',
            parsed.title || '',
            parsed.keywords ? (typeof parsed.keywords === 'string' ? parsed.keywords : parsed.keywords.join(', ')) : '',
            catMap[parsed.category] || parsed.category || '',
            '', 
            parsed.media_type || '',
            res.user_prompt || '',
            res.model_label || '',
            res.generated_at || ''
        ];

        return row.map(v => {
            if (v === null || v === undefined) return '""';
            let str = String(v).replace(/"/g, '""');
            return `"${str}"`;
        }).join(',');
    }

    static downloadCSV(content, timestamp) {
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `AIdoforyou.com_Stock_Metadata_${timestamp}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    static exportSingleCSV(res, timestamp) {
        if (!res) return;
        const headers = ["Filename", "Title", "Keywords", "Category", "Releases", "Media Type", "Additional Context", "AI Model", "Timestamp"];
        let parsed = {};
        try { parsed = this.parseRobustJSON(res.metadata || '{}'); } catch(e){}
        
        let csvContent = headers.join(',') + "\n" + this.compileCSVRow(res, parsed) + "\n";
        this.downloadCSV(csvContent, timestamp);
    }

    static exportBulkCSV(results, timestamp) {
        const headers = ["Filename", "Title", "Keywords", "Category", "Releases", "Media Type", "Additional Context", "AI Model", "Timestamp"];
        let csvContent = headers.join(',') + "\n";
        
        results.forEach(res => {
            let parsed = {};
            try { parsed = this.parseRobustJSON(res.metadata || '{}'); } catch(e){}
            csvContent += this.compileCSVRow(res, parsed) + "\n";
        });
        
        this.downloadCSV(csvContent, timestamp);
    }
};