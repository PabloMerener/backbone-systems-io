# Backbone

## Resolución

Tomando en cuenta el <a href="https://www.correosdemexico.gob.mx/SSLServicios/ConsultaCP/CodigoPostal_Exportar.aspx">datasource</a> (CPdescarga.xls) y el <a href="https://jobs.backbonesystems.io/api/zip-codes/01210">json</a> que debe devolver la api, armé el DER. Luego para optimizar el tiempo de respuesta, en lugar de hacer una query de varios joins a las tablas states, cities, municipalities and settlements, incluí un campo json con dicha información en la tabla zip_codes.

Para pasar la información desde el archivo CPdescarga.xls a la base de datos, utilice la librería <a href="https://laravel-excel.com/">LARAVEL EXCEL</a>. El seeder toma el archivo de entrada (CPdescarga.xls), saltea la primer solapa que contiene una descripción del catálogo, y luego lee cada una de las siguientes solapas que se corresponden con los estados. El proceso de lectura de las solapas tiene un corte de control por la columna "d_codigo". Al finalizar cada corte de control se inserta el registro correspondiente al cógigo postal. Debido a que el seeder se basa en dicho corte de control, los datos siempre deben venir ordenados por código postal.

## Heroku datastore

SERVICE: heroku-postgresql
PLAN: hobby-basic