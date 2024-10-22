import Departamentos from "../../models/departamentos";

export const addDepartamento = async () => {
    // const newUser = await Departamentos.create({
   
    // });

    // console.log('User created:', newUser.toJSON());
};

export const GetDepartamentos= async () => {
    const departamentos = await Departamentos.findAll({
        where: {
            // Aquí pones las condiciones
            dependence: 'Presidencia', // Ejemplo: filtrar por 'Dependencia'
            // Puedes añadir más condiciones, si lo necesitas
        }
    });
        return departamentos;
}