# wc-shop-exporter

Необходимо создать файл *config.json* в корне сайта.

```json
{
    "version": "0.7",
    "version_date": "2023-05-16",
    "headers":
    {
        "ua": "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36"
    },
    "woocommerce": {
        "client": "ck_886c3c0041f15c133b0d5bab64a0253bbed99731",
        "secret": "cs_6f3e642f3ebf1e5d31cb156210b10bd9861ea0e2",
        "options": {
            "timeout": 40000,
            "version": "wc/v3"
        }
    },
    "localsite_url": "https://localsite.tld",
    "scrape_url": "https://targetshopsite.tld/",
    "sitemap":
    {
        "category": "product_cat-sitemap.xml"
    },
    "attributeReplaces": [
        ["4-goda-1-6-1-8-metra", "4 года (1.6-1.8 метра)"]
    ],
    "attributeOptionsNamesToSlugsReplaces": [
        ["Без посадки", "bez-posadki"],
    ],
    "replaces": [
    {
        "target": "",
        "modify": ""
    }],
    "mapper": {
        "product": {
            "description": [
                "class", "goodsCard__descript"
            ],
            "variations": [
                "class", "variations_form cart"
            ],
            "thumbnail": [
                "class", "product-thumbnail"
            ]
        },
        "category": {
            "description": [
                "class", "section-text"
            ]
        }
    }
}
```