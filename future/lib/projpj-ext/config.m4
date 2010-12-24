dnl $Id$
dnl config.m4 for extension projpj

PHP_ARG_ENABLE(projpj, whether to enable projpj support,
[  --enable-projpj           Enable projpj support])

PHP_ARG_WITH(proj, for proj support,
[  --with-proj             Include proj support])

if test "$PHP_PROJPJ" != "no"; then

  Get Proj path
  if test "$PHP_PROJ" != "no" -a "$PHP_PROJ" != "yes"; then
    PROJ_DIR=$PHP_PROJ
  else
    PROJ_DIR="./proj"
  fi

  LIBNAME=proj
  LIBDIR=/usr/local/lib
  INCDIR=/usr/local/include
  LIBSYMBOL=pj_get_default_ctx 
  
  PHP_CHECK_LIBRARY($LIBNAME,$LIBSYMBOL,
  [
    PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $LIBDIR, PROJPJ_SHARED_LIBADD)
    AC_DEFINE(HAVE_LIBPROJ,1,[ ])
    PHP_ADD_INCLUDE($INCDIR)
  ],[
    AC_MSG_ERROR([proj library not found. Check config.log for more information.])
  ],[])
  
  PHP_NEW_EXTENSION(projpj, projpj.c, $ext_shared)
  PHP_SUBST(PROJPJ_SHARED_LIBADD)
  
fi
