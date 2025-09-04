export const COOKIE_MAX_AGE_1_YEAR = 365 * 24 * 60 * 60

export const API_KEY_EDITOR = import.meta.env.VITE_API_KEY_EDITOR;
export const BASE_BACK_STORAGE = `${import.meta.env.VITE_API_BASE_BACK}/storage/`;
export const ROLE_SUPERADMIN_UUID = "21626ff9-4940-4143-879a-0f75b46eadb7";



export const TYPE_CODE_GLOSA_001 = {
  id: "1",
  type_code: "3047",
  name: "RESOLUCION 3047",
}
export const TYPE_CODE_GLOSA_002 = {
  id: "2",
  type_code: "2285",
  name: "Resolucion 2285 de 2023",
}




// REGLAS O CODIGOS PARA LOS SELECECTS DEL FORMULARIO DEL SERVICIO 
export const CODS_SELECT_FORM_SERVICE_MEDICAL_CONSULTATION_CODCONSULTA = [
  "890101",
  "890102",
  "890105",
  "890201",
  "890202",
  "890202",
  "890204",
  "890205",
  "890206",
  "890207",
  "890208",
  "890208",
  "890209",
  "890209",
  "890210",
  "890211",
  "890212",
  "890213",
  "890214",
  "890284",
  "890285",
  "890301",
  "890302",
  "890302",
  "890303",
  "890303",
  "890304",
  "890305",
  "890306",
  "890307",
  "890308",
  "890308",
  "890309",
  "890309",
  "890310",
  "890311",
  "890312",
  "890313",
  "890314",
  "890384",
  "890385",
  "890501",
  "890502",
  "890503",
  "890703",
  "890704",
  "890402",
  "890403",
  "890404",
  "890405",
  "890406",
  "890408",
  "890409",
  "890410",
  "890411",
  "890412",
  "890413",
  "940100",
  "940101",
  "940200",
  "940201",
  "940301",
  "940302",
  "940700",
  "940701",
  "940900",
  "940901",
  "941100",
  "941101",
  "941301",
  "941400",
  "942600",
  "942601",
  "943101",
  "943102",
  "943500",
  "943501",
  "944001",
  "944002",
  "944101",
  "944102",
  "944201",
  "944202",
  "944301",
  "944901",
  "944902",
  "944903",
  "944904",
  "944905",
  "944906",
  "944910",
  "944915",
];
export const CODS_SELECT_FORM_SERVICE_MEDICAL_CONSULTATION_FINALIDADTECNOLOGIASALUD = [
  "12",
  "13",
  "15",
  "16",
  "17",
  "18",
  "19",
  "20",
  "21",
  "22",
  "23",
  "24",
  "25",
  "27",
  "43",
  "44",
];

export const CODS_SELECT_FORM_SERVICE_MEDICAL_CONSULTATION_CAUSAMOTIVOATENCION = [
  "21",
  "22",
  "23",
  "24",
  "25",
  "26",
  "27",
  "28",
  "29",
  "30",
];

export const CODS_SELECT_FORM_SERVICE_TIPODOCUMENTOIDENTIFICACION = [
  "CC",
  "CE",
  "CD",
  "PA",
  "SC",
  "PE",
  "DE",
  "PT",
];

export const CODS_SELECT_FORM_SERVICE_MEDICAL_CONSULTATION_CONCEPTORECAUDO = [
  "02",
  "03",
  "05",
];


export const documentLengthByType: { [key: string]: number } = {
  'CC': 10,  // Cédula de ciudadanía
  'CE': 6,   // Cédula de extranjería
  'CD': 16,  // Carnet diplomático
  'PA': 16,  // Pasaporte
  'SC': 16,  // Salvoconducto
  'PE': 15,  // Permiso especial de permanencia
  'RC': 11,  // Registro civil
  'TI': 11,  // Tarjeta de identidad
  'CN': 9,   // Certificado de nacido vivo
  'AS': 10,  // Adulto sin identificar
  'MS': 12,  // Menor sin identificar
  'DE': 20,  // Documento extranjero
  'PT': 20,  // Permiso temporal
  'SI': 20,  // Sin identificación
  'NI': 12,  // Número de identificación tributario NIT 
  'NV': 20,   // Certificado nacido vivo 
};


export const CODS_SELECT_FORM_SERVICE_PROCEDURE_FINALIDADTECNOLOGIASALUD = [
  "12",
  "13",
  "14",
  "15",
  "16",
  "17",
  "18",
  "19",
  "20",
  "22",
  "23",
  "24",
  "25",
  "26",
  "27",
  "28",
  "29",
  "30",
  "31",
  "32",
  "33",
  "34",
  "35",
  "36",
  "37",
  "38",
  "39",
  "40",
  "41",
  "42",
  "43",
  "44",
];

export const CODS_SELECT_FORM_SERVICE_PROCEDURE_CONCEPTORECAUDO = [
  "01",
  "02",
  "03",
  "05",
];

export const CODS_SELECT_FORM_SERVICE_NEWBORN_TIPODOCUMENTOIDENTIFICACION = [
  'CN',
  'RC',
  'MS'
];


export const CODS_SELECT_FORM_SERVICE_OTHERSERVICE_CONCEPTORECAUDO = [
  "02",
  "03",
  "05",
];

export const CODS_SELECT_FORM_SERVICE_MEDICINE_CONCEPTORECAUDO = [
  "01",
  "03",
  "05",
];

export const CODS_SELECT_FORM_FURIPS1_OWNERDOCUMENTTYPE = [
  'CC',
  'CE',
  'CD',
  'DE',
  'SC',
  'PE',
  'PT',
  'NI',
];

export const CODS_SELECT_FORM_FURIPS1_DRIVERDOCUMENTTYPE = [
  'CC',
  'CE',
  'PA',
  'RC',
  'TI',
  'CD',
  'SC',
  'DE',
  'PE',
  'PT',
];

export const CODS_SELECT_FORM_FURIPS1_DOCTORIDTYPE = [
  'CC',
  'CE',
  'PA',
  'PE',
  'PT',
];

export const CODS_SELECT_FORM_FURTRAN_CLAIMANIDTYPE = [
  'CC',
  'CE',
  'PA',
  'CD',
  'PE',
  'PT',
  'DE',
];


