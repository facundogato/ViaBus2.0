// Tarjeta ViaBus — medidas en mm
W = 85.6;  H = 54;  R = 4;                 // tamaño y esquinas redondeadas
ESP_BASE = 1.6;  ESP_AMARILLO = 0.4;  ESP_QR = 0.4;   // alturas (capas de 0.2)
MOD = 1.2;                                 // tamaño de cada cuadradito del QR
MARGEN = 3;                                // margen claro alrededor del QR (en cuadraditos)
FUENTE = "Liberation Sans:style=Bold";
AZUL = "#060c22";
AMARILLO_C = "#f4b400";

module forma(w, h, r) {
  hull() for (x = [r, w - r], y = [r, h - r]) translate([x, y]) circle(r = r, $fn = 40);
}

// rects: lista de [columna, fila, largo] en cuadraditos; n: lado del QR
module tarjeta(linea1, linea2, tam, numero, n, rects) {
  panel = (n + 2 * MARGEN) * MOD;
  px = 6;                    // el panel del QR va a la izquierda
  py = (H - panel) / 2;
  tx = px + panel + 4;       // donde empieza la zona de texto

  // 1) Base azul
  color(AZUL) linear_extrude(ESP_BASE) forma(W, H, R);

  // 2) Capa amarilla: panel del QR + textos
  color(AMARILLO_C) translate([0, 0, ESP_BASE]) linear_extrude(ESP_AMARILLO) {
    translate([px, py]) square(panel);
    translate([tx, H - 13]) text("VIABUS", size = 6, font = FUENTE);
    translate([tx, H / 2 - 2]) text(linea1, size = tam, font = FUENTE);
    translate([tx, H / 2 - 2 - tam * 1.3]) text(linea2, size = tam, font = FUENTE);
    translate([tx, 5]) text(str("N° ", numero), size = 3.5, font = FUENTE);
  }

  // 3) Cuadraditos oscuros del QR
  color(AZUL) translate([0, 0, ESP_BASE + ESP_AMARILLO]) linear_extrude(ESP_QR)
    translate([px + MARGEN * MOD, py + MARGEN * MOD])
      for (r = rects)
        translate([r[0] * MOD, r[1] * MOD]) square([r[2] * MOD + 0.01, MOD + 0.01]);
}