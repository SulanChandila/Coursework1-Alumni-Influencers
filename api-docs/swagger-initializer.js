window.onload = function() {
  window.ui = SwaggerUIBundle({
    url: "http://localhost/alumini_api_cw/api-docs/swagger.yaml", //Swagger URL
    dom_id: '#swagger-ui',
    deepLinking: true,
    presets: [
      SwaggerUIBundle.presets.apis,
      SwaggerUIStandalonePreset
    ],
    plugins: [
      SwaggerUIBundle.plugins.DownloadUrl
    ],
    layout: "StandaloneLayout"
  });

  //</editor-fold>
};
