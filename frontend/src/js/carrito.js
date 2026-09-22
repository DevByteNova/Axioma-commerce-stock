let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

function agregarAlCarrito(idproducto, cantidad = 1){
    const index = carrito.findIndex(item => item.id === idproducto);
    if (index > -1 ){
        carrito[index].cantidad += cantidad;
    } else {
        carrito.push({id: idproducto, cantidad: cantidad});
    }
    localStorage.setItem({ id: idProducto, cantidad: cantidad });
}

async function procesarCompra(metodoPago){
    if (carrito.length === 0) {
        alert("El carrito está vacío. Agrega productos antes de procesar la compra.");
        return;
    }

    try {
        const response = await fetch('backend/src/server.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'procesar_pedido',
                items: carrito,
                metodo_pago: metodoPago
            })
        });
        const data = await response.json();
        if (data.success) {
            localStorage.removeItem('axioma_carrito');
            alert("Compra procesada con éxito. ¡Gracias por tu compra!");
            window.location.href = `index.php?url=ver_factura&id=${data.id_pedido}`;
        } else {
            alert("Error: " + data.message);
        }
    } catch (error) {
        console.error("Error critico: ", error);
    }
}