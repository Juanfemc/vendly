import fs from "node:fs/promises";
import { SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const outputDir = "outputs/meta-audience-template";
const outputPath = `${outputDir}/plantilla_meta_audiencias_vendly.xlsx`;
const previewPath = `${outputDir}/preview_meta_csv.png`;

await fs.mkdir(outputDir, { recursive: true });

const workbook = Workbook.create();
const data = workbook.worksheets.add("Meta CSV");
const instructions = workbook.worksheets.add("Instrucciones");

const orange = "#FF6B00";
const navy = "#07142F";
const lightOrange = "#FFF1E8";
const lightBlue = "#F4F7FB";
const border = "#D8E2EC";
const muted = "#5F6E86";
const font = "Arial";

data.showGridLines = false;
instructions.showGridLines = false;
data.tabColor = orange;
instructions.tabColor = "#8EA0B8";

data.getRange("A1:J1").values = [["Plantilla para audiencias de Meta"]];
data.getRange("A2:J2").values = [["Pega aqui los contactos de Vendly. Para subir a Meta, exporta esta hoja como CSV y deja solo las columnas que vas a usar."]];
data.mergeCells("A1:J1");
data.mergeCells("A2:J2");

const headers = [
  "email",
  "phone",
  "first_name",
  "last_name",
  "country",
  "value",
  "customer_type",
  "store_name",
  "plan",
  "notes",
];

const examples = [
  [
    "cliente@email.com",
    573001234567,
    "Juan",
    "Perez",
    "CO",
    25000,
    "renovo_alguna_vez",
    "Mi Tienda",
    "premium",
    "Fila de ejemplo. Borra antes de subir a Meta.",
  ],
  [
    "cliente2@email.com",
    573009998877,
    "Maria",
    "Gomez",
    "CO",
    10000,
    "alta_intencion",
    "Tienda Demo",
    "pro",
    "Usa telefonos sin espacios, sin guiones y sin +.",
  ],
];

data.getRange("A4:J4").values = [headers];
data.getRange("A5:A206").format.numberFormat = "@";
data.getRange("B5:B206").format.numberFormat = "0";
data.getRange("C5:E206").format.numberFormat = "@";
data.getRange("G5:J206").format.numberFormat = "@";
data.getRange("A5:J6").values = examples;
data.getRange("A7:J206").values = Array.from({ length: 200 }, () => Array(10).fill(""));

data.getRange("A1:J1").format = {
  font: { name: font, size: 16, bold: true, color: navy },
};
data.getRange("A2:J2").format = {
  font: { name: font, size: 10, italic: true, color: muted },
};
data.getRange("A4:J4").format = {
  fill: navy,
  font: { name: font, size: 10, bold: true, color: "#FFFFFF" },
  verticalAlignment: "center",
  horizontalAlignment: "center",
};
data.getRange("A4:J206").format.borders = { preset: "all", style: "thin", color: border };
data.getRange("A5:J206").format = {
  fill: "#FFFFFF",
  font: { name: font, size: 10, color: navy },
  verticalAlignment: "center",
};
data.getRange("F5:F206").format.numberFormat = "#,##0";
data.getRange("A5:J6").format.fill = lightOrange;

data.getRange("L4:N10").values = [
  ["Campo", "Usar en Meta", "Notas"],
  ["email", "Si", "El mejor dato para emparejar cuentas."],
  ["phone", "Si", "Formato recomendado: 57 + celular, sin + ni espacios."],
  ["first_name", "Si", "Primer nombre."],
  ["last_name", "Si", "Apellido o resto del nombre."],
  ["country", "Si", "Para Colombia usa CO."],
  ["value", "Opcional", "Puede ser 10000 o 25000 segun plan o valor estimado."],
];
data.getRange("L4:N4").format = {
  fill: orange,
  font: { name: font, bold: true, color: "#FFFFFF" },
};
data.getRange("L5:N10").format = {
  fill: "#FFFFFF",
  font: { name: font, size: 10, color: navy },
};
data.getRange("L4:N10").format.borders = { preset: "all", style: "thin", color: border };

data.getRange("A:A").format.columnWidth = 28;
data.getRange("B:B").format.columnWidth = 18;
data.getRange("C:D").format.columnWidth = 16;
data.getRange("E:E").format.columnWidth = 10;
data.getRange("F:F").format.columnWidth = 12;
data.getRange("G:G").format.columnWidth = 22;
data.getRange("H:H").format.columnWidth = 24;
data.getRange("I:I").format.columnWidth = 12;
data.getRange("J:J").format.columnWidth = 38;
data.getRange("K:K").format.columnWidth = 3;
data.getRange("L:N").format.columnWidth = 24;
data.freezePanes.freezeRows(4);

instructions.getRange("A1:F1").values = [["Como usar esta plantilla"]];
instructions.mergeCells("A1:F1");
instructions.getRange("A1:F1").format = {
  font: { name: font, size: 16, bold: true, color: navy },
};
instructions.getRange("A3:B12").values = [
  ["Paso", "Accion"],
  ["1", "Pega la lista exportada desde el servidor en la hoja Meta CSV."],
  ["2", "Borra las dos filas de ejemplo antes de subir el archivo."],
  ["3", "Revisa que email y phone no tengan espacios innecesarios."],
  ["4", "Usa country = CO para Colombia."],
  ["5", "Para publico similar, puedes subir solo email, phone, first_name, last_name y country."],
  ["6", "Guarda o exporta la hoja Meta CSV como CSV UTF-8."],
  ["7", "En Meta, crea una Audiencia personalizada desde Lista de clientes."],
  ["8", "Despues crea Publico similar desde esa audiencia."],
  ["9", "No subas columnas internas si no las necesitas, como notes o store_name."],
];
instructions.getRange("A3:B3").format = {
  fill: orange,
  font: { name: font, bold: true, color: "#FFFFFF" },
};
instructions.getRange("A4:B12").format = {
  font: { name: font, size: 10, color: navy },
  verticalAlignment: "center",
};
instructions.getRange("A3:B12").format.borders = { preset: "all", style: "thin", color: border };
instructions.getRange("A:A").format.columnWidth = 10;
instructions.getRange("B:B").format.columnWidth = 90;

instructions.getRange("D3:F8").values = [
  ["Tipo de lista", "customer_type sugerido", "Uso"],
  ["Renovaron", "renovo_alguna_vez", "Publico similar de mayor calidad."],
  ["Alta intencion", "alta_intencion", "Publico similar secundario o combinado."],
  ["Actuales", "actual", "Retargeting o exclusiones."],
  ["Combinada", "vendly_calificado", "Cuando Meta pida una audiencia mas grande."],
  ["", "", ""],
];
instructions.getRange("D3:F3").format = {
  fill: navy,
  font: { name: font, bold: true, color: "#FFFFFF" },
};
instructions.getRange("D4:F8").format = {
  fill: "#FFFFFF",
  font: { name: font, size: 10, color: navy },
};
instructions.getRange("D3:F8").format.borders = { preset: "all", style: "thin", color: border };
instructions.getRange("D:F").format.columnWidth = 28;
instructions.getRange("A1:F14").format.wrapText = true;

workbook.recalculate();

const inspect = await workbook.inspect({
  kind: "table",
  sheetId: "Meta CSV",
  range: "A1:N10",
  include: "values",
  tableMaxRows: 12,
  tableMaxCols: 14,
});
console.log(inspect.ndjson);

const errors = await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!",
  options: { useRegex: true, maxResults: 50 },
  summary: "final formula error scan",
});
console.log(errors.ndjson);

const preview = await workbook.render({
  sheetName: "Meta CSV",
  range: "A1:N12",
  scale: 1,
  format: "png",
});
await fs.writeFile(previewPath, new Uint8Array(await preview.arrayBuffer()));

const xlsx = await SpreadsheetFile.exportXlsx(workbook);
await xlsx.save(outputPath);
console.log(JSON.stringify({ outputPath, previewPath }));
