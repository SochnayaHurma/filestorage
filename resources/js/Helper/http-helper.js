import {usePage} from "@inertiajs/vue3";

export function httpGet(url, params = {}) {
    return fetch(url,  {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        ...params
    }).then((response) => response.json())
}

export function httpPost(url, data ) {
    const page = usePage();
    return new Promise((resolve, reject) => {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': page.props._csrfToken
            },
            body: JSON.stringify(data)
        }).then(response => {
            if (response.ok) {
                resolve(response.json());
            } else {
                response.json()
                    .then((data) => {
                        reject({response, error: data});
                    })
            }
        })
    });
}
