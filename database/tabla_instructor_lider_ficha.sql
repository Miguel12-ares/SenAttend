create table instructor_lider_ficha (
id int auto_increment primary key,
id_instructor int,
id_ficha int
);
ALTER TABLE instructor_lider_ficha
ADD CONSTRAINT idx_instructor FOREIGN KEY (id_instructor) REFERENCES usuarios(id);
ALTER TABLE instructor_lider_ficha
ADD CONSTRAINT idx_ficha FOREIGN KEY (id_ficha) REFERENCES fichas(id);
show create table instructor_lider;
