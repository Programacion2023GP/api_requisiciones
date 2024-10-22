import { Sequelize, Op } from 'sequelize'; // Asegúrate de importar Op
import User from "../../models/user";
import Departamentos from "../../models/departamentos";

export const addUser = async (user: User) => {
    try {
        console.log(`adding ${JSON.stringify(user, null, 2)}`);
        const newUser = await User.create(user);
        return newUser; 
    } catch (error: any) {
        // Verifica si el error es por violación de unicidad del correo electrónico
        if (error.name === 'SequelizeUniqueConstraintError') {
            // Busca si el campo específico que causa el error es el correo electrónico
            const emailError = error.errors.find((err: any) => err.path === 'email');
            if (emailError) {
                throw new Error('El correo electrónico ya está registrado');
            }
        }
        
        console.error('Error al crear el usuario:', error); // Añade el logging del error
        throw new Error('No se pudo crear el usuario'); 
    }
};


export const DeleteUser = async (id:string)=>{
    try {

        await User.destroy({ where: { id } });
        return `Usuario con ID ${id} eliminado correctamente`;
    }
    catch (error) {
        console.error('Error al crear el usuario:', error); // Añade el logging del error
        throw new Error('No se pudo eliminar al usuario'); 
    }
}


export const GetUsers = async () => {
    const users = await User.findAll({
        attributes: [
            'id',
            'name',
            'paternalname',
            'maternalname',
             'id_group',
            'email',
            [
                Sequelize.fn('CONCAT', 
                    Sequelize.col('name'), 
                    ' ', 
                    Sequelize.col('paternalname'), 
                    ' ', 
                    Sequelize.col('maternalname')
                ), 
                'fullname'
            ],
        ],
        include: [
            {
                model: Departamentos, // Ahora debería funcionar correctamente
                attributes: ['id','group'], // Selecciona los campos del grupo que deseas
                required: true, // Esto asegura que sea un INNER JOIN
                as: 'departamento', // Usa el alias que definiste (opcional)
            },
        ],
        order:[
            ['id', 'ASC'], // Ordenar por la columna 'id' en orden ascendente

        ]
    });
   
    
    return users;
};
export const updateUser = async (id: string, userData: User) => {
    try {
      const user = await User.findByPk(id);
      if (!user) {
        return null; // Si no se encuentra el usuario, devuelve null
      }
      // Actualiza el usuario con los nuevos datos
      await user.update(userData); // Usa userData aquí para evitar conflictos
  
      return user; // Devuelve el usuario actualizado
    } catch (error) {
      console.error('Error al actualizar el usuario:', error);
      throw new Error('No se pudo actualizar el usuario');
    }
  };
  
