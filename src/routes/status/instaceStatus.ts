import { Sequelize, Op } from 'sequelize'; // Asegúrate de importar Op
import Status from '../../models/status';
export const addStatus = async (status: Status) => {
    try {
        const newStatus = await Status.create(status);
        return newStatus; 
    } catch (error: any) {
        console.log(error,error.message);
        // Verifica si el error es por violación de unicidad del correo electrónico
          
                throw new Error('El status no se puede registrar');
            
        }

};


export const DeleteStatus = async (id:string)=>{
    try {

        await Status.destroy({ where: { id } });
        return `Status con ID ${id} eliminado correctamente`;
    }
    catch (error) {
        throw new Error('No se pudo eliminar el status'); 
    }
}


export const GetStatus = async () => {
    const status = await Status.findAll({
    //   where: {
    //     active:1,
        
    //   }
    });
   
    
    return status;
};
export const updateStatus = async (id: string, statusData: Status) => {
    try {
      const user = await Status.findByPk(id);
      if (!user) {
        return null; // Si no se encuentra el usuario, devuelve null
      }
      // Actualiza el usuario con los nuevos datos
      await user.update(statusData); // Usa userData aquí para evitar conflictos
  
      return user; // Devuelve el usuario actualizado
    } catch (error) {
      console.error('Error al actualizar el status:', error);
      throw new Error('No se pudo actualizar el status');
    }
  };
  
