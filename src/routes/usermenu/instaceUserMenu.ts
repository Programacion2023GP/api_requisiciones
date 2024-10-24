import { Sequelize, Op } from "sequelize"; // Asegúrate de importar Op
import UserMenu from "../../models/usermenu";


export const GetUserMenu = async (id:number) => {
  const status = await UserMenu.findAll({
      where: {
        id_user:id,
      }
  });

  return status;
};
