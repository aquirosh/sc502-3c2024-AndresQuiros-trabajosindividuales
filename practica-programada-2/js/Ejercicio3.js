// script.js
let products = [];

document.getElementById('product-form').addEventListener('submit', function(event) {
    event.preventDefault();
    const name = document.getElementById('product-name').value;
    const price = document.getElementById('product-price').value;
    const category = document.getElementById('product-category').value;
    
    if (name && price && category) {
        products.push({ name, price, category });
        document.getElementById('product-form').reset();
        renderProducts();
    }
});

document.getElementById('category-filter').addEventListener('change', function() {
    renderProducts();
});

function renderProducts() {
    const category = document.getElementById('category-filter').value;
    const productList = document.getElementById('product-list');
    productList.innerHTML = '';
    
    products.filter(product => !category || product.category === category)
            .forEach((product, index) => {
                const list = document.createElement('list');
                list.textContent = `${product.name} - $${product.price} (${product.category})`;
                const deleteBtn = document.createElement('button');
                deleteBtn.textContent = 'Eliminar';
                deleteBtn.addEventListener('click', () => {
                    products.splice(index, 1);
                    renderProducts();
                });
                list.appendChild(deleteBtn);
                productList.appendChild(list);
            });
}
