'use strict';
const {
  Model
} = require('sequelize');
module.exports = (sequelize, DataTypes) => {
  class usermenu extends Model {
    /**
     * Helper method for defining associations.
     * This method is not a part of Sequelize lifecycle.
     * The `models/index` file will call this method automatically.
     */
    static associate(models) {
      // define association here
    }
  }
  usermenu.init({
    id_user: DataTypes.NUMBER,
    id_menu: DataTypes.NUMBER,
    read: DataTypes.BOOLEAN,
    write: DataTypes.BOOLEAN,
    delete: DataTypes.BOOLEAN,
    edit: DataTypes.BOOLEAN,
  }, {
    sequelize,
    modelName: 'usermenu',
  });
  return usermenu;
};