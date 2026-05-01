$(function () {
  // Inicialización del botón flotante de WhatsApp
  $("#WAButton").floatingWhatsApp({
    phone: "5491153323937",
    headerTitle: "Contáctanos por WhatsApp", // Título del popup
    popupMessage: "¿En qué podemos ayudarte?", // Mensaje del popup
    showPopup: true, // Habilitar visualización del popup
    buttonImage:
      '<img src="https://static.whatsapp.net/rsrc.php/v3/yP/r/rYZqPCBaG70.png" />', // Imagen del botón
    position: "right", // Posición del botón
  });
});
